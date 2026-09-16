<?php

namespace Database\Factories;

use App\Models\ProcessingRequest;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingRequest>
 */
class ProcessingRequestFactory extends Factory
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
            'reference_no' => fake()->unique()->regexify('PRC-[A-Z]{2}-[0-9]{5}'),
            'sample_no' => fake()->unique()->regexify('[0-9]{5}-[A-Z]{2}'),
            'sample_type' => fake()->sentence(4),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'sub_total' => $subTotal,
            'discount' => 0,
            'total_fee' => $subTotal,
        ];
    }
}
