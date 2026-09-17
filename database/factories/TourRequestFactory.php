<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\TourRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourRequest>
 */
class TourRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_request_id' => ServiceRequest::factory(),
            'message' => fake()->sentence(),
            'no_persons' => fake()->numberBetween(1, 30),
            'no_groups' => fake()->numberBetween(1, 5),
        ];
    }
}
