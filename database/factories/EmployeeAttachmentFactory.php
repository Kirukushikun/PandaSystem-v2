<?php

namespace Database\Factories;

use App\Enums\ConfidentialityTag;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\EmployeeAttachment>
 */
class EmployeeAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->word().'.pdf';

        return [
            'employee_id' => Employee::factory(),
            'uploaded_by' => User::factory()->hrHead(),
            'path' => 'employees/test/legacy/'.$this->faker->uuid().'.pdf',
            'original_name' => $name,
            'size' => $this->faker->numberBetween(50_000, 2_000_000),
            'confidentiality_tag' => ConfidentialityTag::Manila,
        ];
    }
}
