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

test('the seeders build the finalized master data', function () {
    expect(Farm::pluck('name')->all())->toEqualCanonicalizing(['BDL', 'BFC', 'BRD', 'PFC', 'RH'])
        ->and(Department::count())->toBe(11)
        ->and(Department::pluck('name'))->toContain('Poultry', 'Feedmill', 'Swine', 'Human Resources', 'Treasury')
        ->and(User::count())->toBe(8)
        ->and(Employee::count())->toBe(10);
});

test('one role per account: each stage has exactly one holder', function () {
    expect(User::where('is_requestor', true)->sole()->username)->toBe('kreyes')
        ->and(User::where('is_division_head', true)->sole()->username)->toBe('jbautista')
        ->and(User::where('is_dh_head', true)->sole()->username)->toBe('caguirre')
        ->and(User::where('is_hr_head', true)->sole()->username)->toBe('mdelacruz')
        ->and(User::where('is_hr_approver', true)->sole()->username)->toBe('rocampo')
        ->and(User::where('is_final_approver', true)->sole()->username)->toBe('vsalazar')
        ->and(User::where('is_admin', true)->sole()->username)->toBe('admin_it')
        // pinned to match the external auth-system id ahead of go-live
        ->and(User::where('username', 'admin_it')->sole()->id)->toBe(61)
        // HR Head Preparer is one role: mdelacruz carries the preparer flag alongside tnavarro
        ->and(User::where('is_hr_preparer', true)->pluck('username')->all())
        ->toEqualCanonicalizing(['tnavarro', 'mdelacruz']);
});

test('reseeding resets stale permissions instead of stacking them', function () {
    // kreyes was Requestor + Division Head before the one-role split — reseed must clear it
    User::where('username', 'kreyes')->first()->update(['is_division_head' => true]);

    $this->seed(DatabaseSeeder::class);

    $kreyes = User::where('username', 'kreyes')->first();
    expect($kreyes->is_requestor)->toBeTrue()
        ->and($kreyes->is_division_head)->toBeFalse();
});

test('department assignments: the requestor raises for Poultry + Feedmill; the division head heads both', function () {
    expect(User::where('username', 'kreyes')->first()->requestorDepartments->pluck('name')->all())
        ->toEqualCanonicalizing(['Poultry', 'Feedmill'])
        ->and(User::where('username', 'jbautista')->first()->headedDepartments->pluck('name')->all())
        ->toEqualCanonicalizing(['Poultry', 'Feedmill'])
        ->and(User::where('username', 'caguirre')->first()->headedDepartments)->toBeEmpty();
});

test('employees are linked to the finalized farms and departments', function () {
    $lim = Employee::where('employee_no', 'EMP-10233')->first();

    expect($lim->name)->toBe('S. Lim')
        ->and($lim->department->name)->toBe('Feedmill')
        ->and($lim->farm->name)->toBe('PFC');
});

test('reference values in use are guarded; unstaffed departments are deletable', function () {
    expect(Farm::where('name', 'BFC')->first()->isInUse())->toBeTrue()
        ->and(Department::where('name', 'Poultry')->first()->isInUse())->toBeTrue()
        ->and(Department::where('name', 'Treasury')->first()->isInUse())->toBeFalse();
});

test('stage permissions bridge to the PanWorkflow permission keys', function () {
    $kreyes = User::where('username', 'kreyes')->first();
    $vsalazar = User::where('username', 'vsalazar')->first();

    expect($kreyes->hasStagePermission('requestor'))->toBeTrue()
        ->and($kreyes->hasStagePermission('division_head'))->toBeFalse()
        ->and(User::where('username', 'jbautista')->first()->hasStagePermission('division_head'))->toBeTrue()
        ->and($vsalazar->hasStagePermission('final_approver'))->toBeTrue()
        ->and($vsalazar->hasStagePermission('nonsense'))->toBeFalse();
});
