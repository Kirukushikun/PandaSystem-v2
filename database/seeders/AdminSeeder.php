<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The one real account every environment needs from a fresh start — id
 * pinned to match the external company-system id (go-live requirement,
 * since the login flow resolves people by users.id).
 */
class AdminSeeder extends Seeder
{
    private const ALL_PERMS = [
        'is_requestor', 'is_division_head', 'is_hr_preparer', 'is_hr_approver',
        'is_final_approver', 'is_hr_head', 'is_dh_head', 'is_admin',
    ];

    public function run(): void
    {
        $user = User::firstOrNew(['username' => 'admin_it']);
        $user->fill([
            'name' => 'IT Admin',
            'email' => 'admin_it@bfcgroup.org',
            'position' => 'Systems Administrator',
            'farm_id' => Farm::where('name', 'BFC')->value('id'),
            ...array_fill_keys(self::ALL_PERMS, false),
            'is_admin' => true,
        ]);

        // 'id' is deliberately not fillable — set directly since it must match the
        // external auth-system id.
        $user->id = 61;
        $user->save();
    }
}
