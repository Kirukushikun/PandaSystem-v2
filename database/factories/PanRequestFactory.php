<?php

namespace Database\Factories;

use App\Enums\ActionType;
use App\Enums\ConfidentialityTag;
use App\Enums\PanOrigin;
use App\Enums\PanStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PanRequest>
 */
class PanRequestFactory extends Factory
{
    private static int $sequence = 90000; // clear of seeded/mockup references

    public function definition(): array
    {
        return [
            'reference' => 'PAN-'.now()->year.'-'.str_pad((string) self::$sequence++, 5, '0', STR_PAD_LEFT),
            'employee_id' => Employee::factory(),
            'department_id' => Department::factory(),
            'action_type' => $this->faker->randomElement(ActionType::cases()),
            'justification' => $this->faker->sentence(12),
            'status' => PanStatus::Draft,
            'confidentiality_tag' => ConfidentialityTag::Untagged,
            'origin' => PanOrigin::Requestor,
            'requested_by' => User::factory()->requestor(),
        ];
    }

    public function status(PanStatus $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function manila(): static
    {
        return $this->state(['confidentiality_tag' => ConfidentialityTag::Manila]);
    }

    public function tarlac(): static
    {
        return $this->state(['confidentiality_tag' => ConfidentialityTag::Tarlac]);
    }

    public function hrOriginated(): static
    {
        return $this->state([
            'origin' => PanOrigin::Hr,
            'requested_by' => null,
            'status' => PanStatus::AwaitingTag,
        ]);
    }
}
