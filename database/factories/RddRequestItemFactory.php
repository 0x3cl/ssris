<?php

namespace Database\Factories;

use App\Models\RddRequest;
use App\Models\RddRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RddRequestItem>
 */
class RddRequestItemFactory extends Factory
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
            'rdd_request_id' => RddRequest::factory(),
            'item' => fake()->words(3, true),
            'specification' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_fee' => $unitFee,
            'total_fee' => $quantity * $unitFee,
        ];
    }
}
