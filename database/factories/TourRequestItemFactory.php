<?php

namespace Database\Factories;

use App\Models\TourRequest;
use App\Models\TourRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourRequestItem>
 */
class TourRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tour_request_id' => TourRequest::factory(),
        ];
    }
}
