<?php

namespace Database\Factories;

use App\Models\FeedbackQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackQuestion>
 */
class FeedbackQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(),
        ];
    }
}
