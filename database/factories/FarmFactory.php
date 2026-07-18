<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Farm>
 */
class FarmFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company().' Farm',
        ];
    }
}
