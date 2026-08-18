<?php

use App\Enums\PanStatus;
use App\Livewire\Admin\Employees;
use App\Livewire\Admin\UserAccess;
use App\Livewire\Admin\Users;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

test('the accounts list shows everyone with their stage summary and flags', function () {
    User::factory()->requestor()->divisionHead()->create(['name' => 'K. Reyes']);
    User::factory()->hrHead()->create(['name' => 'M. Dela Cruz']);

    Livewire::test(Users::class)
        ->assertSee('K. Reyes')
        ->assertSee('Requestor · Division Head')
        ->assertSee('HR Head')
        ->set('filter', 'admins')
        ->assertDontSee('K. Reyes')
        ->assertSee($this->admin->name);
});

test('user access saves permissions, departments, farm, and position together', function () {
    $account = User::factory()->requestor()->create();
    $account->requestorDepartments()->attach($old = Department::factory()->create());
    $poultry = Department::factory()->create(['name' => 'Poultry']);
    $farm = Farm::factory()->create();

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->call('togglePerm', 'division_head')
        ->call('removeDepartment', 'requestsFor', $old->id)
        ->call('addDepartment', 'heads', (string) $poultry->id)
        ->set('farmId', $farm->id)
        ->set('position', 'Operations Manager')
        ->call('save')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->is_division_head)->toBeTrue()
        ->and($account->is_requestor)->toBeTrue() // untouched toggle stays
        ->and($account->requestorDepartments)->toBeEmpty()
        ->and($account->headedDepartments->pluck('id')->all())->toBe([$poultry->id])
        ->and($account->farm_id)->toBe($farm->id)
        ->and($account->position)->toBe('Operations Manager');
});

test('an admin cannot switch off their own admin flag', function () {
    Livewire::test(UserAccess::class, ['user' => $this->admin->username])
        ->call('togglePerm', 'admin')
        ->assertSet('perms.admin', true);
});

test('a locked-out account shows the unlock banner, and unlocking clears it without waiting 15 minutes', function () {
    $account = User::factory()->requestor()->create();
    app(App\Services\LoginLockoutService::class)->recordFailure($account->email);
    app(App\Services\LoginLockoutService::class)->recordFailure($account->email);
    app(App\Services\LoginLockoutService::class)->recordFailure($account->email);

    expect(app(App\Services\LoginLockoutService::class)->isLocked($account->email))->toBeTrue();

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->assertSet('isLocked', true)
        ->assertSee('locked out')
        ->call('unlockAccount')
        ->assertSet('isLocked', false);

    expect(app(App\Services\LoginLockoutService::class)->isLocked($account->email))->toBeFalse();
});

test('an account with no lockout shows no banner', function () {
    $account = User::factory()->requestor()->create();

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->assertSet('isLocked', false)
        ->assertDontSee('locked out');
});

test('the e-signature uploads to the private disk and serves via the esign route', function () {
    Storage::fake();
    $account = User::factory()->create();

    Livewire::test(UserAccess::class, ['user' => $account->username])
        ->set('esign', UploadedFile::fake()->image('sig.png'))
        ->call('save')
        ->assertHasNoErrors();

    $account->refresh();
    expect($account->esign_path)->toBe('esigns/'.$account->username.'.png');
    Storage::assertExists($account->esign_path);

    $this->get('/users/'.$account->id.'/esign')->assertOk();
});

test('employees can be added and edited through the shared modal', function () {
    $department = Department::factory()->create();
    $farm = Farm::factory()->create();

    Livewire::test(Employees::class)
        ->call('startCreate')
        ->set('name', 'Q. Test')
        ->set('employee_no', 'emp-99001')
        ->set('department_id', $department->id)
        ->set('position', 'Tester')
        ->set('farm_id', $farm->id)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::where('employee_no', 'EMP-99001')->sole(); // uppercased on save

    Livewire::test(Employees::class)
        ->call('startEdit', $employee->id)
        ->set('position', 'Senior Tester')
        ->call('save')
        ->assertHasNoErrors();

    expect($employee->fresh()->position)->toBe('Senior Tester');
});

test('duplicate employee IDs are rejected', function () {
    $existing = Employee::factory()->create();
    $department = Department::factory()->create();
    $farm = Farm::factory()->create();

    Livewire::test(Employees::class)
        ->call('startCreate')
        ->set('name', 'D. Uplicate')
        ->set('employee_no', $existing->employee_no)
        ->set('department_id', $department->id)
        ->set('position', 'Clone')
        ->set('farm_id', $farm->id)
        ->call('save')
        ->assertHasErrors(['employee_no' => 'unique']);
});

test('removal is policy-blocked while an ongoing PAN exists — drafts do not block', function () {
    $blocked = Employee::factory()->create();
    PanRequest::factory()->status(PanStatus::InPreparation)->create(['employee_id' => $blocked->id]);

    Livewire::test(Employees::class)
        ->call('remove', $blocked->id)
        ->assertForbidden();
    expect($blocked->fresh()->trashed())->toBeFalse();

    $free = Employee::factory()->create();
    PanRequest::factory()->status(PanStatus::Draft)->create(['employee_id' => $free->id]);

    Livewire::test(Employees::class)->call('remove', $free->id);
    expect($free->fresh()->trashed())->toBeTrue()
        ->and(PanRequest::where('employee_id', $free->id)->count())->toBe(1); // history kept
});

test('a non-admin, non-preparer cannot touch the roster even by direct action', function () {
    $employee = Employee::factory()->create();
    $this->actingAs(User::factory()->requestor()->create());

    Livewire::test(Employees::class)
        ->call('startCreate')
        ->assertForbidden();
});

test('an HR Preparer can reach the roster and add/edit employees, but not remove them', function () {
    $department = Department::factory()->create();
    $farm = Farm::factory()->create();
    $this->actingAs(User::factory()->hrPreparer()->create());

    $this->get(route('admin.employees'))->assertOk();

    Livewire::test(Employees::class)
        ->call('startCreate')
        ->set('name', 'N. Hire')
        ->set('employee_no', 'emp-77001')
        ->set('department_id', $department->id)
        ->set('position', 'Farmhand')
        ->set('farm_id', $farm->id)
        ->call('save')
        ->assertHasNoErrors();

    $employee = Employee::where('employee_no', 'EMP-77001')->sole();

    Livewire::test(Employees::class)
        ->call('remove', $employee->id)
        ->assertForbidden();
});
