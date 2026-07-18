<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Farm;
use Illuminate\Database\Seeder;

/**
 * Seeds the master data only (accounts, reference values, roster) — PANs start
 * from an empty slate. To restore the mockup's sample PANs for 1:1 screen
 * comparison, run: php artisan db:seed --class=PanSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
        ]);

        // Prune reference values dropped from the finalized lists — only after
        // users/employees were remapped above, and never while still in use.
        Department::whereNotIn('name', ReferenceDataSeeder::DEPARTMENTS)->get()
            ->each(fn (Department $department) => $department->isInUse() || $department->delete());
        Farm::whereNotIn('name', ReferenceDataSeeder::FARMS)->get()
            ->each(fn (Farm $farm) => $farm->isInUse() || $farm->delete());
    }
}
