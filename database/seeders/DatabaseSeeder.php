<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * Fresh-start slate: reference values + the one real admin account (id 61,
 * pinned to the external company-system id). No demo accounts, no sample
 * roster, no PANs. To layer in the local dev/demo fixtures on top:
 *   php artisan db:seed --class=DemoUserSeeder
 *   php artisan db:seed --class=DemoEmployeeSeeder
 *   php artisan db:seed --class=PanSeeder   (needs the two above first)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            AdminSeeder::class,
        ]);

        // Prune reference values dropped from the finalized lists — only after
        // users/employees were remapped above, and never while still in use.
        Department::whereNotIn('name', ReferenceDataSeeder::DEPARTMENTS)->get()
            ->each(fn (Department $department) => $department->isInUse() || $department->delete());
        Farm::whereNotIn('name', ReferenceDataSeeder::FARMS)->get()
            ->each(fn (Farm $farm) => $farm->isInUse() || $farm->delete());
    }
}
