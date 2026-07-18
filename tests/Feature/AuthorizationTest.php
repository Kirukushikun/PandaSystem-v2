<?php

use App\Enums\PanStatus;
use App\Models\Department;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Route gates — every module group requires its stage permission
|--------------------------------------------------------------------------
*/

test('module routes 403 without the matching stage permission', function (string $route, string $ability) {
    $nobody = User::factory()->create(); // all 8 booleans false

    $this->actingAs($nobody)->get($route)->assertForbidden();

    $somebody = User::factory()->{$ability}()->create();
    $this->actingAs($somebody)->get($route)->assertOk();
})->with([
    'requests / requestor' => ['/requests', 'requestor'],
    'division / divisionHead' => ['/division', 'divisionHead'],
    'preparation / hrPreparer' => ['/preparation', 'hrPreparer'],
    'employees lens / hrPreparer' => ['/employees', 'hrPreparer'],
    'hr-approval / hrApprover' => ['/hr-approval', 'hrApprover'],
    'final-approval / finalApprover' => ['/final-approval', 'finalApprover'],
    'admin users / admin' => ['/admin/users', 'admin'],
    'maintenance / admin' => ['/maintenance/logs', 'admin'],
]);

/*
|--------------------------------------------------------------------------
| PanRequestPolicy — the Manila/Tarlac view matrix
|--------------------------------------------------------------------------
*/

test('a requestor sees their own PAN but never a colleague\'s (the v1 direct-link hole)', function () {
    $mine = PanRequest::factory()->status(PanStatus::WithDivisionHead)->create();
    $owner = $mine->requestedBy;
    $otherRequestor = User::factory()->requestor()->create();

    expect($owner->can('view', $mine))->toBeTrue()
        ->and($otherRequestor->can('view', $mine))->toBeFalse();

    // and over HTTP, by direct link:
    $this->actingAs($otherRequestor)->get('/requests/'.$mine->reference)->assertForbidden();
});

test('drafts are visible to nobody but their author', function () {
    $draft = PanRequest::factory()->status(PanStatus::Draft)->create();

    $divisionHead = User::factory()->divisionHead()->create();
    $divisionHead->headedDepartments()->attach($draft->department_id);

    expect($draft->requestedBy->can('view', $draft))->toBeTrue()
        ->and($divisionHead->can('view', $draft))->toBeFalse()
        ->and(User::factory()->hrPreparer()->create()->can('view', $draft))->toBeFalse();
});

test('a division head sees routine PANs of departments they head — and no others', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::WithDivisionHead)->create();

    $headOfDept = User::factory()->divisionHead()->create();
    $headOfDept->headedDepartments()->attach($pan->department_id);

    $headElsewhere = User::factory()->divisionHead()->create();
    $headElsewhere->headedDepartments()->attach(Department::factory()->create());

    expect($headOfDept->can('view', $pan))->toBeTrue()
        ->and($headElsewhere->can('view', $pan))->toBeFalse();
});

test('Manila PANs: the department\'s own head is locked out; the DH Head gets in — any department', function () {
    $manila = PanRequest::factory()->manila()->status(PanStatus::ForConfirmation)->create();

    $ownHead = User::factory()->divisionHead()->create();
    $ownHead->headedDepartments()->attach($manila->department_id);

    $dhHead = User::factory()->dhHead()->create(); // heads no department pivot at all

    expect($ownHead->can('view', $manila))->toBeFalse()
        ->and($dhHead->can('view', $manila))->toBeTrue();

    $this->actingAs($ownHead)->get('/division/'.$manila->reference)->assertForbidden();
});

test('Manila PANs: ordinary preparers are locked out; HR Head preparers get in', function () {
    $manila = PanRequest::factory()->manila()->status(PanStatus::InPreparation)->create();

    expect(User::factory()->hrPreparer()->create()->can('view', $manila))->toBeFalse()
        ->and(User::factory()->hrHead()->create()->can('view', $manila))->toBeTrue();
});

test('HR and Final approvers see every submitted PAN — no confidentiality distinction at their stages', function () {
    $manila = PanRequest::factory()->manila()->status(PanStatus::ForHrApproval)->create();
    $tarlac = PanRequest::factory()->tarlac()->status(PanStatus::ForFinalApproval)->create();

    $hrApprover = User::factory()->hrApprover()->create();
    $finalApprover = User::factory()->finalApprover()->create();

    expect($hrApprover->can('view', $manila))->toBeTrue()
        ->and($hrApprover->can('view', $tarlac))->toBeTrue()
        ->and($finalApprover->can('view', $manila))->toBeTrue()
        ->and($finalApprover->can('view', $tarlac))->toBeTrue();
});

test('an admin has no implicit PAN access — administration is not PAN visibility', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::InPreparation)->create();

    expect(User::factory()->admin()->create()->can('view', $pan))->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The other entry points: attachment + print (v1's open doors)
|--------------------------------------------------------------------------
*/

test('print and attachment routes enforce the same policy per record', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::InPreparation)->create();
    App\Models\PanForm::factory()->create(['pan_request_id' => $pan->id]);
    $outsider = User::factory()->requestor()->create();

    $this->actingAs($outsider)->get('/pan/'.$pan->reference.'/print')->assertForbidden();
    $this->actingAs($outsider)->get('/pan/'.$pan->reference.'/attachment')->assertForbidden();

    $this->actingAs($pan->requestedBy)->get('/pan/'.$pan->reference.'/print')->assertOk();
    // owner without an uploaded file: authorized but nothing to download
    $this->actingAs($pan->requestedBy)->get('/pan/'.$pan->reference.'/attachment')->assertNotFound();
});

test('printing a PAN with no prepared paperwork is a 404, not a blank sheet', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::WithDivisionHead)->create();

    $this->actingAs($pan->requestedBy)->get('/pan/'.$pan->reference.'/print')->assertNotFound();
});
