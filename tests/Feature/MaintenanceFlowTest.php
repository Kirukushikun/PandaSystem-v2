<?php

use App\Enums\PanStatus;
use App\Livewire\Maintenance\Backups;
use App\Livewire\Maintenance\DangerZone;
use App\Livewire\Maintenance\Logs;
use App\Livewire\Maintenance\ReferenceValues;
use App\Models\AccessLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->admin()->create());
});

/*
|--------------------------------------------------------------------------
| Logs
|--------------------------------------------------------------------------
*/

test('the logs screen shows sign-in attempts and the derived workflow trail', function () {
    AccessLog::create(['email' => 'kreyes@bfcgroup.org', 'success' => true, 'ip_address' => '10.0.0.1', 'user_agent' => 'test']);
    AccessLog::create(['email' => 'ghost@bfcgroup.org', 'success' => false, 'ip_address' => '10.0.0.9', 'user_agent' => 'test']);

    $pan = PanRequest::factory()->status(PanStatus::ReturnedToPreparer)->create();
    $pan->returns()->create([
        'action' => 'return_to_preparer', 'from_status' => PanStatus::ForHrApproval,
        'to_status' => PanStatus::ReturnedToPreparer, 'reason' => 'Wage number mismatch',
        'returned_by' => User::factory()->hrApprover()->create(['name' => 'R. Ocampo'])->id,
    ]);

    Livewire::test(Logs::class)
        ->assertSee('kreyes@bfcgroup.org')
        ->assertSee('Failed')
        ->assertSee('R. Ocampo')
        ->assertSee('Wage number mismatch');
});

/*
|--------------------------------------------------------------------------
| Reference values
|--------------------------------------------------------------------------
*/

test('reference values add with unique names and refuse to delete anything in use', function () {
    $used = Farm::factory()->create();
    Employee::factory()->create(['farm_id' => $used->id]);
    $unused = Department::factory()->create();

    Livewire::test(ReferenceValues::class)
        ->set('newFarm', $used->name)
        ->call('addFarm')
        ->assertHasErrors(['newFarm' => 'unique'])
        ->set('newFarm', 'BRD-2')
        ->call('addFarm')
        ->assertHasNoErrors()
        ->call('removeFarm', $used->id)   // in use — silently guarded with a toast
        ->call('removeDept', $unused->id);

    expect(Farm::where('name', 'BRD-2')->exists())->toBeTrue()
        ->and(Farm::whereKey($used->id)->exists())->toBeTrue()
        ->and(Department::whereKey($unused->id)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Backups
|--------------------------------------------------------------------------
*/

test('a manual backup shells out to mysqldump and lists the dump', function () {
    Storage::fake();
    Process::fake(function ($process) {
        // the service verifies the file exists — fake the dump being written
        foreach ($process->command as $arg) {
            if (str_starts_with((string) $arg, '--result-file=')) {
                file_put_contents(substr($arg, 14), '-- fake dump');
            }
        }

        return Process::result();
    });

    Livewire::test(Backups::class)->call('runBackup');

    Process::assertRan(fn ($process) => str_contains($process->command[0] ?? '', 'mysqldump'));
    expect(app(App\Services\BackupService::class)->list())->toHaveCount(1);
});

/*
|--------------------------------------------------------------------------
| Danger zone
|--------------------------------------------------------------------------
*/

test('preview counts the real blast radius and the typed count executes it', function () {
    Storage::fake();
    PanRequest::factory()->count(3)->status(PanStatus::Filed)->create();

    $test = Livewire::test(DangerZone::class)
        ->call('selectMode', 'wipe', 'all')
        ->call('preview', 'wipe')
        ->assertSet('counts.wipe', 3)
        ->call('openConfirm', 'wipe')
        ->set('confirmInput', '2') // wrong count typed
        ->call('queueConfirmed');

    expect(PanRequest::withTrashed()->count())->toBe(3); // nothing happened

    $test->set('confirmInput', '3')->call('queueConfirmed');

    expect(PanRequest::withTrashed()->count())->toBe(0);
});

test('purging attachments keeps the PAN records but clears the files', function () {
    Storage::fake();
    $pan = PanRequest::factory()->status(PanStatus::Filed)->create();
    Storage::put('pans/'.$pan->reference.'/doc.pdf', 'x');
    \App\Models\PanAttachment::factory()->create([
        'pan_request_id' => $pan->id, 'path' => 'pans/'.$pan->reference.'/doc.pdf',
    ]);

    Livewire::test(DangerZone::class)
        ->call('selectMode', 'attach', 'range')
        ->set('from.attach', now()->subDay()->toDateString())
        ->set('to.attach', now()->addDay()->toDateString())
        ->call('preview', 'attach')
        ->assertSet('counts.attach', 1)
        ->call('openConfirm', 'attach')
        ->set('confirmInput', '1')
        ->call('queueConfirmed');

    $pan->refresh();
    expect($pan->attachments)->toHaveCount(0)
        ->and(PanRequest::count())->toBe(1);
    Storage::assertMissing('pans/'.$pan->reference.'/doc.pdf');
});

test('log purge by year deletes only that year', function () {
    $old = AccessLog::create(['email' => 'a@b.c', 'success' => true, 'ip_address' => '1', 'user_agent' => 't']);
    $old->forceFill(['created_at' => '2025-03-01'])->save(); // created_at is not mass-assignable
    AccessLog::create(['email' => 'a@b.c', 'success' => true, 'ip_address' => '1', 'user_agent' => 't']);

    Livewire::test(DangerZone::class)
        ->call('selectMode', 'dlog', 'year')
        ->set('year.dlog', '2025')
        ->call('preview', 'dlog')
        ->assertSet('counts.dlog', 1)
        ->call('openConfirm', 'dlog')
        ->set('confirmInput', '1')
        ->call('queueConfirmed');

    expect(AccessLog::count())->toBe(1);
});

test('changing a filter invalidates the previewed count', function () {
    PanRequest::factory()->status(PanStatus::Filed)->create();

    Livewire::test(DangerZone::class)
        ->call('selectMode', 'wipe', 'year')
        ->set('year.wipe', (string) now()->year)
        ->call('preview', 'wipe')
        ->assertSet('counts.wipe', 1)
        ->set('year.wipe', '2022')
        ->assertSet('counts.wipe', null);
});
