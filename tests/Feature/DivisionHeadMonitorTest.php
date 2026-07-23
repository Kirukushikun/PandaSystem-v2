<?php

use App\Enums\PanStatus;
use App\Livewire\DivisionHead\Monitor;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->head = User::factory()->divisionHead()->create();
    $this->head->headedDepartments()->attach($this->department);
    $this->employee = Employee::factory()->create(['department_id' => $this->department->id]);

    $this->actingAs($this->head);
});

test('monitor shows the department\'s PANs at any stage, but never drafts, Manila rows, or other departments', function () {
    $awaiting = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70101',
    ]);
    $inPrep = PanRequest::factory()->status(PanStatus::InPreparation)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70102',
    ]);
    $filed = PanRequest::factory()->status(PanStatus::Filed)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70103',
    ]);
    PanRequest::factory()->status(PanStatus::Draft)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70104',
    ]);
    PanRequest::factory()->status(PanStatus::InPreparation)->manila()->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70105',
    ]);
    PanRequest::factory()->status(PanStatus::WithDivisionHead)->create(['reference' => 'PAN-2026-70106']);

    Livewire::test(Monitor::class)
        ->assertSee('PAN-2026-70101') // awaiting — still visible here too
        ->assertSee('PAN-2026-70102') // in progress, already past the DH stage
        ->assertSee('PAN-2026-70103') // fully completed
        ->assertDontSee('PAN-2026-70104') // draft — in nobody's queue
        ->assertDontSee('PAN-2026-70105') // Manila — DH Head territory
        ->assertDontSee('PAN-2026-70106'); // someone else's department
});

test('monitor has no decision buttons — it is view-only even for a PAN currently awaiting this head\'s decision', function () {
    $awaiting = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id,
    ]);

    Livewire::test(Monitor::class)
        ->assertDontSee('Approve')
        ->assertDontSee('Return to Requestor');
});

test('the completed filter isolates terminal statuses; in-progress excludes them', function () {
    $filed = PanRequest::factory()->status(PanStatus::Filed)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70201',
    ]);
    $inPrep = PanRequest::factory()->status(PanStatus::InPreparation)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70202',
    ]);

    Livewire::test(Monitor::class)
        ->set('filter', 'completed')
        ->assertSee('PAN-2026-70201')
        ->assertDontSee('PAN-2026-70202')
        ->set('filter', 'progress')
        ->assertDontSee('PAN-2026-70201')
        ->assertSee('PAN-2026-70202');
});

test('a DH Head monitors ONLY Manila PANs, across all departments', function () {
    $dhHead = User::factory()->dhHead()->create();

    PanRequest::factory()->status(PanStatus::Filed)->create([
        'employee_id' => $this->employee->id, 'department_id' => $this->department->id, 'reference' => 'PAN-2026-70301',
    ]); // routine
    PanRequest::factory()->status(PanStatus::InPreparation)->manila()
        ->create(['reference' => 'PAN-2026-70302']); // Manila, some other department

    $this->actingAs($dhHead);
    Livewire::test(Monitor::class)
        ->assertSee('PAN-2026-70302')
        ->assertDontSee('PAN-2026-70301');
});
