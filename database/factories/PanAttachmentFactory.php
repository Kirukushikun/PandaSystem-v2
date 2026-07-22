<?php

namespace Database\Factories;

use App\Models\PanRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\PanAttachment>
 */
class PanAttachmentFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->word().'.pdf';

        return [
            'pan_request_id' => PanRequest::factory(),
            'path' => 'pans/test/'.$this->faker->uuid().'.pdf',
            'original_name' => $name,
            'size' => $this->faker->numberBetween(50_000, 2_000_000),
        ];
    }
}
