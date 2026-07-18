<?php

namespace Database\Seeders;

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
    }
}
