<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * Sample roster for local dev/testing — NOT run by default (see
 * DatabaseSeeder). Remapped onto the finalized farms/departments
 * (BDL/BFC/BRD/PFC/RH · Poultry/Feedmill/Swine/…).
 */
class DemoEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $farms = Farm::pluck('id', 'name');
        $departments = Department::pluck('id', 'name');

        $employees = [
            ['employee_no' => 'EMP-10233', 'name' => 'S. Lim',        'department' => 'Feedmill',        'position' => 'Mill Operator',      'farm' => 'PFC'],
            ['employee_no' => 'EMP-10490', 'name' => 'N. Fernandez',  'department' => 'Human Resources', 'position' => 'HR Generalist',      'farm' => 'BFC'],
            ['employee_no' => 'EMP-10064', 'name' => 'D. Torres',     'department' => 'Poultry',         'position' => 'Poultry Caretaker',  'farm' => 'BFC'],
            ['employee_no' => 'EMP-10387', 'name' => 'J. Ramos',      'department' => 'Poultry',         'position' => 'Hatchery Aide',      'farm' => 'BRD'],
            ['employee_no' => 'EMP-10077', 'name' => 'L. Bautista',   'department' => 'Poultry',         'position' => 'Farm Technician I',  'farm' => 'BFC'],
            ['employee_no' => 'EMP-10422', 'name' => 'R. Villanueva', 'department' => 'Poultry',         'position' => 'Farm Helper',        'farm' => 'BFC'],
            ['employee_no' => 'EMP-10301', 'name' => 'A. Santos',     'department' => 'Poultry',         'position' => 'Poultry Caretaker',  'farm' => 'BFC'],
            ['employee_no' => 'EMP-10119', 'name' => 'C. Mercado',    'department' => 'Swine',           'position' => 'Farmhand',           'farm' => 'BDL'],
            ['employee_no' => 'EMP-10255', 'name' => 'P. Aquino',     'department' => 'Swine',           'position' => 'Farm Technician I',  'farm' => 'RH'],
            ['employee_no' => 'EMP-10198', 'name' => 'E. Garcia',     'department' => 'Feedmill',        'position' => 'Farm Technician II', 'farm' => 'PFC'],
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
