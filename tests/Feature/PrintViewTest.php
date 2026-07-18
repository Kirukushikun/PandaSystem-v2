<?php

use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the print view renders the real PAN across all three copies', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::Approved)->create([
        'action_type' => 'promotion',
    ]);
    PanForm::factory()->create([
        'pan_request_id' => $pan->id,
        'employment_status' => EmploymentStatus::Regular,
        'action_reference' => [
            ['field' => 'position', 'from' => 'Farm Technician II', 'to' => 'Senior Farm Technician'],
            ['field' => 'basic', 'from' => '28,100.00', 'to' => '31,600.00'],
        ],
        'remarks' => 'Per Q2 2026 performance review cycle.',
    ]);

    $response = $this->actingAs($pan->requestedBy)->get('/pan/'.$pan->reference.'/print');

    $response->assertOk()
        ->assertSee('PROMOTION')
        ->assertSee($pan->employee->name)
        ->assertSee($pan->employee->employee_no)
        ->assertSee('Senior Farm Technician')
        ->assertSee('₱ 31,600.00')
        ->assertSee('Per Q2 2026 performance review cycle.')
        ->assertSee('EMPLOYEE COPY')
        ->assertSee('FOR 201 FILING')
        ->assertSee('PAYROLL COPY')
        // fully local assets — no CDN reach-outs
        ->assertDontSee('cdn.tailwindcss.com')
        ->assertDontSee('cdnjs.cloudflare.com')
        ->assertDontSee('fonts.googleapis.com');
});

test('a Wage Order print heads with its wage number', function () {
    $pan = PanRequest::factory()->tarlac()->status(PanStatus::Approved)->create([
        'action_type' => 'wage-order',
    ]);
    PanForm::factory()->create(['pan_request_id' => $pan->id, 'wage_no' => 'NCR-26']);

    $this->actingAs($pan->requestedBy)->get('/pan/'.$pan->reference.'/print')
        ->assertOk()
        ->assertSee('WAGE ORDER NO. NCR-26');
});

test('the esign route serves nothing for users without a signature', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/users/'.$user->id.'/esign')->assertNotFound();
});
