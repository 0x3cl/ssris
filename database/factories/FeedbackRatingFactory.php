<?php

namespace Database\Factories;

use App\Models\FeedbackRating;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackRating>
 */
class FeedbackRatingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'value' => (string) fake()->numberBetween(1, 5),
        ];
    }
}
