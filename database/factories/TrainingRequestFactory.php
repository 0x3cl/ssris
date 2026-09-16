<?php

namespace Database\Factories;

use App\Models\ServiceRequest;
use App\Models\TrainingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingRequest>
 */
class TrainingRequestFactory extends Factory
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
            'training_course_requested' => fake()->sentence(3),
            'estimated_participants' => fake()->numberBetween(10, 100),
            'proposed_training_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'proposed_training_venue' => fake()->address(),
            'beneficiary_name' => fake()->company(),
            'purpose_of_training' => fake()->paragraph(),
            'assigned_trainer' => fake()->name(),
            'assigned_assistant_trainer' => fake()->name(),
            'official_course_title' => fake()->sentence(4),
            'approved_training_duration' => fake()->numberBetween(1, 5).' day(s)',
            'training_type' => fake()->randomElement(['in-house', 'regional', 'virtual']),
            'special_type_details' => null,
        ];
    }
}
