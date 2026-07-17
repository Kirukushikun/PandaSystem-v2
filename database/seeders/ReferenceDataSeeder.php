<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * Farms/Sites and Departments from the mockup's Reference Values screen
 * (+ Accounting, which appears in the Add Employee modal's department list).
 */
class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'San Rafael Farm',
            'Sta. Maria Feedmill',
            'Main Office',
            'Pampanga Grower Site', // 0 employees — the deletable sample row
        ] as $farm) {
            Farm::firstOrCreate(['name' => $farm]);
        }

        foreach ([
            'Broiler Operations',
            'Hatchery',
            'Feedmill',
            'Sales & Distribution',
            'Corporate Office',
            'Accounting',
            'Aqua Ventures', // 0 heads · 0 employees — the deletable sample row
        ] as $department) {
            Department::firstOrCreate(['name' => $department]);
        }
    }
}
