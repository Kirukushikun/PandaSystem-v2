<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One account per role for from-the-start testing. Every permission boolean is
 * reset explicitly on each run so a role change here always wins over old data.
 * (HR Head carries is_hr_preparer too — an "HR Head Preparer" is one role.)
 */
class UserSeeder extends Seeder
{
    private const ALL_PERMS = [
        'is_requestor', 'is_division_head', 'is_hr_preparer', 'is_hr_approver',
        'is_final_approver', 'is_hr_head', 'is_dh_head', 'is_admin',
    ];

    public function run(): void
    {
        $farms = Farm::pluck('id', 'name');

        $users = [
            ['username' => 'kreyes',    'name' => 'K. Reyes',     'position' => 'Farm Supervisor II',       'farm' => 'BFC', 'perms' => ['is_requestor']],
            ['username' => 'jbautista', 'name' => 'J. Bautista',  'position' => 'Operations Manager',       'farm' => 'BFC', 'perms' => ['is_division_head']],
            ['username' => 'tnavarro',  'name' => 'T. Navarro',   'position' => 'HR Officer',               'farm' => 'BFC', 'perms' => ['is_hr_preparer']],
            ['username' => 'mdelacruz', 'name' => 'M. Dela Cruz', 'position' => 'Sr. HR Officer',           'farm' => 'BFC', 'perms' => ['is_hr_preparer', 'is_hr_head']],
            ['username' => 'caguirre',  'name' => 'C. Aguirre',   'position' => 'AVP — Corporate Services', 'farm' => 'BFC', 'perms' => ['is_dh_head']],
            ['username' => 'rocampo',   'name' => 'R. Ocampo',    'position' => 'HR Manager',               'farm' => 'BFC', 'perms' => ['is_hr_approver']],
            ['username' => 'vsalazar',  'name' => 'V. Salazar',   'position' => 'VP — Operations',          'farm' => 'BFC', 'perms' => ['is_final_approver']],
            // id pinned to the external company-system id (go-live: users.id must match the auth API)
            ['username' => 'admin_it',  'name' => 'IT Admin',     'position' => 'Systems Administrator',    'farm' => 'BFC', 'perms' => ['is_admin'], 'id' => 61],
        ];

        foreach ($users as $data) {
            $user = User::firstOrNew(['username' => $data['username']]);
            $user->fill([
                'name' => $data['name'],
                'email' => $data['username'].'@bfcgroup.org', // org-standard login identity
                'position' => $data['position'],
                'farm_id' => $farms[$data['farm']],
                ...array_fill_keys(self::ALL_PERMS, false),
                ...array_fill_keys($data['perms'], true),
            ]);

            // 'id' is deliberately not fillable — set directly when a fixed id is pinned
            // (e.g. to match the external auth-system id ahead of go-live).
            if (isset($data['id'])) {
                $user->id = $data['id'];
            }

            $user->save();
        }

        // Department assignments (independent of the booleans; co-heads supported):
        // the Requestor raises PANs for Poultry + Feedmill, the Division Head heads both.
        $departments = Department::pluck('id', 'name');
        $assignments = [
            //             requests for                                      heads
            'kreyes' => [[$departments['Poultry'], $departments['Feedmill']], []],
            'jbautista' => [[], [$departments['Poultry'], $departments['Feedmill']]],
        ];

        // Sync BOTH pivots for every account so stale assignments never survive a reseed.
        foreach (User::all() as $user) {
            [$requests, $heads] = $assignments[$user->username] ?? [[], []];
            $user->requestorDepartments()->sync($requests);
            $user->headedDepartments()->sync($heads);
        }
    }
}
