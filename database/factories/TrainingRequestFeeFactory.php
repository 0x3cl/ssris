<?php

namespace Database\Factories;

use App\Models\TrainingRequest;
use App\Models\TrainingRequestFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingRequestFee>
 */
class TrainingRequestFeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_request_id' => TrainingRequest::factory(),
            'reference_no' => fake()->unique()->regexify('TRN-[A-Z]{2}-[0-9]{5}'),
            'date_time' => fake()->dateTimeBetween('-1 week', 'now'),
            'particulars' => fake()->sentence(4),
            'duration' => fake()->numberBetween(1, 5).' day(s)',
            'no_participants' => fake()->numberBetween(10, 100),
            'net_amount_due' => fake()->randomFloat(2, 500, 5000),
            'bill_no' => null,
            'or_no' => null,
        ];
    }
}
