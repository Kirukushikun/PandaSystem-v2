<?php

namespace Database\Factories;

use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_id' => $this->faker->unique()->numerify('EXT-####'),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'name' => $this->faker->name(),
            'position' => $this->faker->jobTitle(),
            'farm_id' => Farm::factory(),
            'is_requestor' => false,
            'is_division_head' => false,
            'is_hr_preparer' => false,
            'is_hr_approver' => false,
            'is_final_approver' => false,
            'is_hr_head' => false,
            'is_dh_head' => false,
            'is_admin' => false,
            'is_proxy_approver' => false,
        ];
    }

    public function requestor(): static
    {
        return $this->state(['is_requestor' => true]);
    }

    public function divisionHead(): static
    {
        return $this->state(['is_division_head' => true]);
    }

    public function hrPreparer(): static
    {
        return $this->state(['is_hr_preparer' => true]);
    }

    public function hrApprover(): static
    {
        return $this->state(['is_hr_approver' => true]);
    }

    public function finalApprover(): static
    {
        return $this->state(['is_final_approver' => true]);
    }

    public function hrHead(): static
    {
        return $this->state(['is_hr_preparer' => true, 'is_hr_head' => true]);
    }

    public function dhHead(): static
    {
        return $this->state(['is_division_head' => true, 'is_dh_head' => true]);
    }

    public function admin(): static
    {
        return $this->state(['is_admin' => true]);
    }

    public function proxyApprover(): static
    {
        return $this->state(['is_proxy_approver' => true]);
    }
}
