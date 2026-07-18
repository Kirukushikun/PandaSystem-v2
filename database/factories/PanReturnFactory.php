<?php

namespace Database\Factories;

use App\Enums\PanStatus;
use App\Models\PanRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PanReturn>
 */
class PanReturnFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pan_request_id' => PanRequest::factory(),
            'action' => 'return_to_requestor',
            'from_status' => PanStatus::WithDivisionHead,
            'to_status' => PanStatus::ReturnedToRequestor,
            'reason' => 'Incomplete supporting document',
            'details' => $this->faker->sentence(),
            'returned_by' => User::factory()->divisionHead(),
        ];
    }
}
