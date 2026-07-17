<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The 7 accounts from the mockup's User Accounts screen, permissions included.
 * K. Reyes's department assignments come from the User Access detail screen.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $farms = Farm::pluck('id', 'name');

        $users = [
            ['username' => 'kreyes',    'name' => 'K. Reyes',     'position' => 'Farm Supervisor II',       'farm' => 'San Rafael Farm', 'perms' => ['is_requestor', 'is_division_head']],
            ['username' => 'mdelacruz', 'name' => 'M. Dela Cruz', 'position' => 'Sr. HR Officer',           'farm' => 'Main Office',     'perms' => ['is_requestor', 'is_division_head', 'is_hr_preparer', 'is_hr_head']],
            ['username' => 'caguirre',  'name' => 'C. Aguirre',   'position' => 'AVP — Corporate Services', 'farm' => 'Main Office',     'perms' => ['is_division_head', 'is_dh_head']],
            ['username' => 'tnavarro',  'name' => 'T. Navarro',   'position' => 'HR Officer',               'farm' => 'Main Office',     'perms' => ['is_hr_preparer']],
            ['username' => 'rocampo',   'name' => 'R. Ocampo',    'position' => 'HR Manager',               'farm' => 'Main Office',     'perms' => ['is_hr_approver']],
            ['username' => 'vsalazar',  'name' => 'V. Salazar',   'position' => 'VP — Operations',          'farm' => 'Main Office',     'perms' => ['is_final_approver']],
            ['username' => 'admin_it',  'name' => 'IT Admin',     'position' => 'Systems Administrator',    'farm' => 'Main Office',     'perms' => ['is_admin']],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['username'].'@bfcgroup.org', // org-standard login identity
                    'position' => $data['position'],
                    'farm_id' => $farms[$data['farm']],
                    ...array_fill_keys($data['perms'], true),
                ]
            );
        }

        // K. Reyes per the User Access screen: requests for Broiler Operations + Hatchery,
        // heads Broiler Operations (independent assignments; co-heads supported).
        $kreyes = User::where('username', 'kreyes')->first();
        $departments = Department::pluck('id', 'name');
        $kreyes->requestorDepartments()->sync([$departments['Broiler Operations'], $departments['Hatchery']]);
        $kreyes->headedDepartments()->sync([$departments['Broiler Operations']]);
    }
}
