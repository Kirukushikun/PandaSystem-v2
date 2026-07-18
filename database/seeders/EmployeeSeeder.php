<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * The 6 roster rows from the mockup's Employee Directory.
 */
class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $farms = Farm::pluck('id', 'name');
        $departments = Department::pluck('id', 'name');

        $employees = [
            ['employee_no' => 'EMP-10233', 'name' => 'S. Lim',        'department' => 'Feedmill',           'position' => 'Mill Operator',     'farm' => 'Sta. Maria Feedmill'],
            ['employee_no' => 'EMP-10490', 'name' => 'N. Fernandez',  'department' => 'Corporate Office',   'position' => 'HR Generalist',     'farm' => 'Main Office'],
            ['employee_no' => 'EMP-10064', 'name' => 'D. Torres',     'department' => 'Broiler Operations', 'position' => 'Poultry Caretaker', 'farm' => 'San Rafael Farm'],
            ['employee_no' => 'EMP-10387', 'name' => 'J. Ramos',      'department' => 'Hatchery',           'position' => 'Hatchery Aide',     'farm' => 'San Rafael Farm'],
            ['employee_no' => 'EMP-10077', 'name' => 'L. Bautista',   'department' => 'Broiler Operations', 'position' => 'Farm Technician I', 'farm' => 'San Rafael Farm'],
            ['employee_no' => 'EMP-10422', 'name' => 'R. Villanueva', 'department' => 'Broiler Operations', 'position' => 'Farm Helper',       'farm' => 'San Rafael Farm'],
            // These two appear in the mockup's Requestor list (not the Directory screen)
            ['employee_no' => 'EMP-10301', 'name' => 'A. Santos',     'department' => 'Broiler Operations', 'position' => 'Poultry Caretaker', 'farm' => 'San Rafael Farm'],
            ['employee_no' => 'EMP-10119', 'name' => 'C. Mercado',    'department' => 'Hatchery',           'position' => 'Hatchery Aide',     'farm' => 'San Rafael Farm'],
            // These two appear only in the mockup's Division Head queue
            ['employee_no' => 'EMP-10255', 'name' => 'P. Aquino',     'department' => 'Broiler Operations', 'position' => 'Farm Technician I', 'farm' => 'San Rafael Farm'],
            ['employee_no' => 'EMP-10198', 'name' => 'E. Garcia',     'department' => 'Broiler Operations', 'position' => 'Farm Technician II', 'farm' => 'San Rafael Farm'],
        ];

        foreach ($employees as $data) {
            Employee::updateOrCreate(
                ['employee_no' => $data['employee_no']],
                [
                    'name' => $data['name'],
                    'farm_id' => $farms[$data['farm']],
                    'department_id' => $departments[$data['department']],
                    'position' => $data['position'],
                ]
            );
        }
    }
}
