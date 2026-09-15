<?php

namespace Database\Factories;

use App\Models\RddRequest;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RddRequest>
 */
class RddRequestFactory extends Factory
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
            'reference_no' => fake()->unique()->regexify('CDABUS-[0-9]{5}'),
            'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
            'sub_total' => $subTotal,
            'discount' => 0,
            'total_fee' => $subTotal,
        ];
    }
}
