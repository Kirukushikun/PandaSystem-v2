<?php

namespace Database\Seeders;

use App\Enums\ActionType;
use App\Enums\ConfidentialityTag;
use App\Enums\EmploymentStatus;
use App\Enums\PanStatus;
use App\Models\Employee;
use App\Models\PanForm;
use App\Models\PanRequest;
use App\Models\PanReturn;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Sample PANs mirroring the mockup so real screens compare 1:1.
 * K. Reyes's five requests (the Requestor list) + a few mid-workflow PANs
 * other modules reference (HR Prep queue).
 */
class PanSeeder extends Seeder
{
    public function run(): void
    {
        $kreyes = User::where('username', 'kreyes')->first();
        $mdelacruz = User::where('username', 'mdelacruz')->first();
        $employees = Employee::pluck('id', 'employee_no');
        $employeeDepts = Employee::pluck('department_id', 'employee_no');

        $pans = [
            // K. Reyes's Requestor list, exactly as the mockup shows it
            ['reference' => 'PAN-2026-00358', 'employee' => 'EMP-10422', 'type' => ActionType::Regularization,   'status' => PanStatus::Draft,               'by' => $kreyes, 'submitted' => null],
            ['reference' => 'PAN-2026-00351', 'employee' => 'EMP-10387', 'type' => ActionType::Promotion,        'status' => PanStatus::ReturnedToRequestor, 'by' => $kreyes, 'submitted' => '2026-07-10'],
            ['reference' => 'PAN-2026-00347', 'employee' => 'EMP-10301', 'type' => ActionType::SalaryAlignment,  'status' => PanStatus::WithDivisionHead,    'by' => $kreyes, 'submitted' => '2026-07-08'],
            ['reference' => 'PAN-2026-00332', 'employee' => 'EMP-10119', 'type' => ActionType::InterimAllowance, 'status' => PanStatus::InPreparation,       'by' => $kreyes, 'submitted' => '2026-07-02', 'tag' => ConfidentialityTag::Tarlac],
            ['reference' => 'PAN-2026-00298', 'employee' => 'EMP-10077', 'type' => ActionType::WageOrder,        'status' => PanStatus::Filed,               'by' => $kreyes, 'submitted' => '2026-06-19', 'tag' => ConfidentialityTag::Tarlac, 'filed' => '2026-06-30'],

            // Mid-workflow PANs the HR Prep screens reference
            ['reference' => 'PAN-2026-00341', 'employee' => 'EMP-10490', 'type' => ActionType::ChangeOfPosition, 'status' => PanStatus::InPreparation,       'by' => $mdelacruz, 'submitted' => '2026-07-07', 'tag' => ConfidentialityTag::Manila],
            ['reference' => 'PAN-2026-00338', 'employee' => 'EMP-10233', 'type' => ActionType::WageOrder,        'status' => PanStatus::ReturnedToPreparer,  'by' => $mdelacruz, 'submitted' => '2026-07-05', 'tag' => ConfidentialityTag::Tarlac],
            ['reference' => 'PAN-2026-00311', 'employee' => 'EMP-10064', 'type' => ActionType::Regularization,   'status' => PanStatus::Approved,            'by' => $mdelacruz, 'submitted' => '2026-06-24', 'tag' => ConfidentialityTag::Tarlac],

            // Division Head queue rows unique to that screen (seeded under mdelacruz so
            // K. Reyes's Requestor list stays exactly the mockup's five)
            ['reference' => 'PAN-2026-00344', 'employee' => 'EMP-10255', 'type' => ActionType::LateralTransfer,  'status' => PanStatus::WithDivisionHead,    'by' => $mdelacruz, 'submitted' => '2026-07-06'],
            ['reference' => 'PAN-2026-00339', 'employee' => 'EMP-10198', 'type' => ActionType::Promotion,        'status' => PanStatus::ForConfirmation,     'by' => $mdelacruz, 'submitted' => '2026-07-03', 'tag' => ConfidentialityTag::Tarlac],
        ];

        foreach ($pans as $data) {
            PanRequest::updateOrCreate(
                ['reference' => $data['reference']],
                [
                    'employee_id' => $employees[$data['employee']],
                    'department_id' => $employeeDepts[$data['employee']],
                    'action_type' => $data['type'],
                    'justification' => 'Seeded from the mockup sample data.',
                    'status' => $data['status'],
                    'confidentiality_tag' => $data['tag'] ?? ConfidentialityTag::Untagged,
                    'requested_by' => $data['by']->id,
                    'submitted_at' => $data['submitted'],
                    'filed_at' => $data['filed'] ?? null,
                ]
            );
        }

        // Prepared PAN forms — the two rows whose Show view appends the "+ PAN Details"
        // extension in the mockup (E. Garcia's promotion, D. Torres's regularization).
        $tnavarro = User::where('username', 'tnavarro')->first();

        $forms = [
            'PAN-2026-00339' => [
                'date_hired' => '2023-02-02',
                'employment_status' => EmploymentStatus::Regular,
                'doe_from' => '2026-08-16',
                'action_reference' => [
                    ['field' => 'section', 'from' => 'Broiler Production A', 'to' => 'Broiler Production A'],
                    ['field' => 'place', 'from' => 'San Rafael Farm', 'to' => 'San Rafael Farm'],
                    ['field' => 'head', 'from' => 'K. Reyes', 'to' => 'K. Reyes'],
                    ['field' => 'position', 'from' => 'Farm Technician II', 'to' => 'Senior Farm Technician'],
                    ['field' => 'joblevel', 'from' => 'JL-4', 'to' => 'JL-5'],
                    ['field' => 'basic', 'from' => '28,100.00', 'to' => '31,600.00'],
                ],
                'remarks' => 'Per Q2 2026 performance review cycle. Effectivity aligned with payroll cutoff.',
            ],
            'PAN-2026-00311' => [
                'date_hired' => '2025-12-16',
                'employment_status' => EmploymentStatus::Regular,
                'doe_from' => '2026-07-01',
                'action_reference' => [
                    ['field' => 'section', 'from' => 'Broiler Production B', 'to' => 'Broiler Production B'],
                    ['field' => 'place', 'from' => 'San Rafael Farm', 'to' => 'San Rafael Farm'],
                    ['field' => 'head', 'from' => 'K. Reyes', 'to' => 'K. Reyes'],
                    ['field' => 'position', 'from' => 'Poultry Caretaker', 'to' => 'Poultry Caretaker'],
                    ['field' => 'joblevel', 'from' => 'JL-2', 'to' => 'JL-2'],
                    // Leave Credits sits BEFORE Basic Pay in the finalized reference order
                    ['field' => 'leavecredits', 'from' => '—', 'to' => 'SL - 1.25 | VL - 1.25'],
                    ['field' => 'basic', 'from' => '17,800.00', 'to' => '18,900.00'],
                ],
                'remarks' => 'Completed the 6-month probationary period with a passing evaluation.',
            ],
        ];

        foreach ($forms as $reference => $form) {
            $pan = PanRequest::where('reference', $reference)->first();
            PanForm::updateOrCreate(
                ['pan_request_id' => $pan->id],
                [...$form, 'prepared_by' => $tnavarro->id]
            );
        }

        // The mockup's return: PAN-2026-00351 sent back for its attachment
        $returned = PanRequest::where('reference', 'PAN-2026-00351')->first();
        PanReturn::updateOrCreate(
            ['pan_request_id' => $returned->id, 'action' => 'return_to_requestor'],
            [
                'from_status' => PanStatus::WithDivisionHead,
                'to_status' => PanStatus::ReturnedToRequestor,
                'reason' => 'Incomplete supporting document',
                'details' => 'Attach the signed evaluation form — the uploaded copy is missing page 2.',
                'returned_by' => $kreyes->id,
            ]
        );
    }
}
