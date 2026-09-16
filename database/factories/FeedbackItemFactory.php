<?php

namespace Database\Factories;

use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackItem>
 */
class FeedbackItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_dimension_id' => FeedbackDimension::factory(),
            'description' => fake()->sentence(),
        ];
    }
}
