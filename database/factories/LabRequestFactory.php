<?php

namespace Database\Factories;

use App\Models\LabRequest;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabRequest>
 */
class LabRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subTotal = fake()->randomFloat(2, 500, 5000);

        return [
            'service_request_id' => ServiceRequest::factory(),
            'quotation_no' => fake()->unique()->regexify('[0-9]{2}-[0-9]{2}-[0-9]{3}-LAB'),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'test_category' => fake()->word(),
            'sample_type' => fake()->word(),
            'sub_total' => $subTotal,
            'discount' => 0,
            'total_fee' => $subTotal,
        ];
    }
}
