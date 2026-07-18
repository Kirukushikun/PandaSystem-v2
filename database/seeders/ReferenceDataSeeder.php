<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * The finalized reference values (settled 2026-07-18, replacing the mockup's
 * sample farms/departments). DatabaseSeeder prunes rows not on these lists
 * once users/employees have been remapped.
 */
class ReferenceDataSeeder extends Seeder
{
    public const FARMS = ['BDL', 'BFC', 'BRD', 'PFC', 'RH'];

    public const DEPARTMENTS = [
        'Accounting',
        'Audit',
        'Feedmill',
        'General Services',
        'Human Resources',
        'IT and Security Services',
        'Poultry',
        'Purchasing',
        'Sales & Marketing',
        'Swine',
        'Treasury',
    ];

    public function run(): void
    {
        foreach (self::FARMS as $farm) {
            Farm::firstOrCreate(['name' => $farm]);
        }

        foreach (self::DEPARTMENTS as $department) {
            Department::firstOrCreate(['name' => $department]);
        }
    }
}
