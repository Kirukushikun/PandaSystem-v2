<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeds the mockup's sample data (panda-ui-concept.html is the UI contract),
 * so every real screen can be compared 1:1 against the scaffold/mockup.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ReferenceDataSeeder::class,
            UserSeeder::class,
            EmployeeSeeder::class,
            PanSeeder::class,
        ]);
    }
}
