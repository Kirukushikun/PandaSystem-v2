<?php

namespace Database\Factories;

use App\Enums\EmploymentStatus;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PanForm>
 */
class PanFormFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pan_request_id' => PanRequest::factory(),
            'date_hired' => $this->faker->dateTimeBetween('-6 years', '-1 year'),
            'employment_status' => EmploymentStatus::Probationary,
            'doe_from' => $this->faker->dateTimeBetween('now', '+2 months'),
            'action_reference' => [
                ['field' => 'section', 'from' => 'Sales — North Luzon', 'to' => 'Sales — North Luzon'],
                ['field' => 'place', 'from' => 'San Fernando Depot', 'to' => 'San Fernando Depot'],
                ['field' => 'head', 'from' => 'J. Villegas', 'to' => 'J. Villegas'],
                ['field' => 'position', 'from' => 'Sales Clerk', 'to' => 'Sales Clerk'],
                ['field' => 'joblevel', 'from' => 'JL-2', 'to' => 'JL-2'],
                ['field' => 'basic', 'from' => '19000', 'to' => '21500'],
            ],
            'prepared_by' => User::factory()->hrPreparer(),
        ];
    }
}
