<?php

namespace Database\Factories;

use App\Models\LabRequest;
use App\Models\LabRequestItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabRequestItem>
 */
class LabRequestItemFactory extends Factory
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
            'lab_request_id' => LabRequest::factory(),
            'test' => fake()->words(3, true),
            'method' => fake()->sentence(),
            'quantity' => $quantity,
            'unit_fee' => $unitFee,
            'total_fee' => $quantity * $unitFee,
        ];
    }
}
