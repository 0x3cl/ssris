<?php

namespace Database\Factories;

use App\Models\FeedbackLink;
use App\Models\FeedbackResponse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackResponse>
 */
class FeedbackResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feedback_link_id' => FeedbackLink::factory(),
            'ratings' => [],
            'answers' => [],
            'snapshot' => ['dimensions' => [], 'ratings' => [], 'questions' => []],
        ];
    }
}
