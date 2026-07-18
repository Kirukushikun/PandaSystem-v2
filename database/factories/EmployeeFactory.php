<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_no' => 'EMP-'.$this->faker->unique()->numberBetween(10000, 99999),
            'name' => $this->faker->name(),
            'farm_id' => Farm::factory(),
            'department_id' => Department::factory(),
            'position' => $this->faker->jobTitle(),
        ];
    }
}
