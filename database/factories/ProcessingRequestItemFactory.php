<?php

namespace Database\Factories;

use App\Models\ProcessingRequest;
use App\Models\ProcessingRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingRequestItem>
 */
class ProcessingRequestItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitFee = fake()->randomFloat(2, 50, 500);

        return [
            'processing_request_id' => ProcessingRequest::factory(),
            'item' => fake()->words(3, true),
            'weight' => fake()->randomFloat(2, 1, 50).' kg',
            'quantity' => $quantity,
            'unit_fee' => $unitFee,
            'total_fee' => $quantity * $unitFee,
        ];
    }
}
