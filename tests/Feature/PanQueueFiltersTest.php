<?php

use App\Enums\ActionType;
use App\Enums\PanStatus;
use App\Livewire\FinalApprover\Queue as FinalApproverQueue;
use App\Livewire\HrPreparation\Queue as HrPreparationQueue;
use App\Livewire\Requestor\Index as RequestorIndex;
use App\Models\Department;
use App\Models\Employee;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| FiltersPanRequests — sort, type of action, department — across roles
|--------------------------------------------------------------------------
*/

test('Requestor queue: type-of-action filter narrows the list', function () {
    $requestor = User::factory()->requestor()->create();
    $wage = PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'action_type' => ActionType::WageOrder, 'reference' => 'PAN-2026-30001']);
    $promo = PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'action_type' => ActionType::Promotion, 'reference' => 'PAN-2026-30002']);

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->set('actionTypeFilter', ActionType::WageOrder->value)
        ->assertSee('PAN-2026-30001')
        ->assertDontSee('PAN-2026-30002');
});

test('Requestor queue: department filter narrows the list', function () {
    $requestor = User::factory()->requestor()->create();
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();
    $requestor->requestorDepartments()->attach([$deptA->id, $deptB->id]);

    $inA = PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'department_id' => $deptA->id, 'reference' => 'PAN-2026-30003']);
    $inB = PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'department_id' => $deptB->id, 'reference' => 'PAN-2026-30004']);

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->set('departmentFilter', $deptA->id)
        ->assertSee('PAN-2026-30003')
        ->assertDontSee('PAN-2026-30004');
});

test('Requestor queue: sort by employee name works both directions', function () {
    $requestor = User::factory()->requestor()->create();
    $alice = Employee::factory()->create(['name' => 'Alice Aquino']);
    $zack = Employee::factory()->create(['name' => 'Zack Zabala']);

    PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'employee_id' => $alice->id, 'reference' => 'PAN-2026-30005']);
    PanRequest::factory()->status(PanStatus::WithDivisionHead)
        ->create(['requested_by' => $requestor->id, 'employee_id' => $zack->id, 'reference' => 'PAN-2026-30006']);

    $az = Livewire::actingAs($requestor)->test(RequestorIndex::class)->set('sort', 'employee_az');
    $azOrder = $az->viewData('pans')->pluck('reference')->all();
    expect($azOrder)->toBe(['PAN-2026-30005', 'PAN-2026-30006']);

    $za = Livewire::actingAs($requestor)->test(RequestorIndex::class)->set('sort', 'employee_za');
    $zaOrder = $za->viewData('pans')->pluck('reference')->all();
    expect($zaOrder)->toBe(['PAN-2026-30006', 'PAN-2026-30005']);
});

test('HR Preparation queue: department and type-of-action filters also narrow the stats tiles', function () {
    $preparer = User::factory()->hrPreparer()->create();
    $deptA = Department::factory()->create();
    $deptB = Department::factory()->create();

    PanRequest::factory()->status(PanStatus::AwaitingTag)
        ->create(['department_id' => $deptA->id, 'action_type' => ActionType::WageOrder, 'reference' => 'PAN-2026-30007']);
    PanRequest::factory()->status(PanStatus::AwaitingTag)
        ->create(['department_id' => $deptB->id, 'action_type' => ActionType::Promotion, 'reference' => 'PAN-2026-30008']);

    $filtered = Livewire::actingAs($preparer)->test(HrPreparationQueue::class)
        ->set('departmentFilter', $deptA->id)
        ->assertSee('PAN-2026-30007')
        ->assertDontSee('PAN-2026-30008');

    expect($filtered->viewData('stats')['prepare'])->toBe(1);
});

test('Final Approver queue: filters and sort apply to the collection-based worklist', function () {
    $finalApprover = User::factory()->finalApprover()->create();
    $alice = Employee::factory()->create(['name' => 'Alice Aquino']);
    $zack = Employee::factory()->create(['name' => 'Zack Zabala']);

    PanRequest::factory()->status(PanStatus::ForFinalApproval)
        ->create(['employee_id' => $alice->id, 'action_type' => ActionType::WageOrder, 'reference' => 'PAN-2026-30009']);
    PanRequest::factory()->status(PanStatus::ForFinalApproval)
        ->create(['employee_id' => $zack->id, 'action_type' => ActionType::Promotion, 'reference' => 'PAN-2026-30010']);

    Livewire::actingAs($finalApprover)->test(FinalApproverQueue::class)
        ->set('actionTypeFilter', ActionType::Promotion->value)
        ->assertSee('PAN-2026-30010')
        ->assertDontSee('PAN-2026-30009');

    $sorted = Livewire::actingAs($finalApprover)->test(FinalApproverQueue::class)->set('sort', 'employee_za');
    expect($sorted->viewData('pans')->pluck('reference')->all())->toBe(['PAN-2026-30010', 'PAN-2026-30009']);
});

/*
|--------------------------------------------------------------------------
| Collapsible filters panel — server-state toggle, not a client classList
| toggle, specifically so a wire:model.live select inside it can't get its
| own re-render reset by Livewire's DOM morph (see FiltersPanRequests).
|--------------------------------------------------------------------------
*/

test('the filters panel is collapsed by default', function () {
    $requestor = User::factory()->requestor()->create();

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->assertSet('showFilters', false)
        ->assertDontSee('All types');
});

test('the panel stays open across a filter change once expanded', function () {
    // wire:click="$toggle('showFilters')" is a magic Livewire action, not directly
    // callable via the test harness's ->call() — ->set() on the same public
    // property exercises the identical server-state path the real click uses.
    $requestor = User::factory()->requestor()->create();

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->set('showFilters', true)
        ->assertSee('All types')
        ->set('sort', 'oldest')
        ->assertSet('showFilters', true)
        ->assertSee('All types');
});

test('setting a filter shows the active-filter dot even while the panel is collapsed', function () {
    $requestor = User::factory()->requestor()->create();
    $department = Department::factory()->create();

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->assertDontSee('A filter is applied')
        ->set('departmentFilter', $department->id)
        ->assertSet('showFilters', false)
        ->assertSee('A filter is applied');
});

test('Clear all resets sort, type, and department in one action', function () {
    $requestor = User::factory()->requestor()->create();
    $department = Department::factory()->create();

    Livewire::actingAs($requestor)->test(RequestorIndex::class)
        ->set('sort', 'oldest')
        ->set('actionTypeFilter', ActionType::Promotion->value)
        ->set('departmentFilter', $department->id)
        ->call('clearPanFilters')
        ->assertSet('sort', 'newest')
        ->assertSet('actionTypeFilter', null)
        ->assertSet('departmentFilter', null)
        ->assertDontSee('A filter is applied');
});

test('Clear all on HR Preparation also resets the tag filter', function () {
    $hrHead = User::factory()->hrHead()->create();

    Livewire::actingAs($hrHead)->test(HrPreparationQueue::class)
        ->set('tagFilter', 'manila')
        ->set('sort', 'oldest')
        ->call('clearPanFilters')
        ->assertSet('tagFilter', 'all')
        ->assertSet('sort', 'newest');
});
