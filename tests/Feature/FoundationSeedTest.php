<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

test('the seeders mirror the mockup sample data', function () {
    expect(Farm::count())->toBe(4)
        ->and(Department::count())->toBe(7)
        ->and(User::count())->toBe(7)
        ->and(Employee::count())->toBe(6);
});

test('K. Reyes matches the User Access screen: permissions and department assignments', function () {
    $kreyes = User::where('username', 'kreyes')->first();

    expect($kreyes->is_requestor)->toBeTrue()
        ->and($kreyes->is_division_head)->toBeTrue()
        ->and($kreyes->is_hr_preparer)->toBeFalse()
        ->and($kreyes->is_admin)->toBeFalse()
        ->and($kreyes->farm->name)->toBe('San Rafael Farm')
        ->and($kreyes->requestorDepartments->pluck('name')->all())
        ->toEqualCanonicalizing(['Broiler Operations', 'Hatchery'])
        ->and($kreyes->headedDepartments->pluck('name')->all())
        ->toBe(['Broiler Operations']);
});

test('flags land on the right people: HR Head, DH Head, Admin', function () {
    expect(User::where('username', 'mdelacruz')->first()->is_hr_head)->toBeTrue()
        ->and(User::where('username', 'caguirre')->first()->is_dh_head)->toBeTrue()
        ->and(User::where('username', 'admin_it')->first()->is_admin)->toBeTrue()
        ->and(User::where('is_hr_head', true)->count())->toBe(1)
        ->and(User::where('is_dh_head', true)->count())->toBe(1)
        ->and(User::where('is_admin', true)->count())->toBe(1);
});

test('employees are linked to their mockup farm and department', function () {
    $lim = Employee::where('employee_no', 'EMP-10233')->first();

    expect($lim->name)->toBe('S. Lim')
        ->and($lim->department->name)->toBe('Feedmill')
        ->and($lim->farm->name)->toBe('Sta. Maria Feedmill');
});

test('reference values in use are guarded; the 0-use samples are deletable', function () {
    expect(Farm::where('name', 'San Rafael Farm')->first()->isInUse())->toBeTrue()
        ->and(Farm::where('name', 'Pampanga Grower Site')->first()->isInUse())->toBeFalse()
        ->and(Department::where('name', 'Broiler Operations')->first()->isInUse())->toBeTrue()
        ->and(Department::where('name', 'Aqua Ventures')->first()->isInUse())->toBeFalse();
});

test('stage permissions bridge to the PanWorkflow permission keys', function () {
    $kreyes = User::where('username', 'kreyes')->first();
    $vsalazar = User::where('username', 'vsalazar')->first();

    expect($kreyes->hasStagePermission('requestor'))->toBeTrue()
        ->and($kreyes->hasStagePermission('division_head'))->toBeTrue()
        ->and($kreyes->hasStagePermission('final_approver'))->toBeFalse()
        ->and($vsalazar->hasStagePermission('final_approver'))->toBeTrue()
        ->and($vsalazar->hasStagePermission('nonsense'))->toBeFalse();
});
