<?php

namespace Database\Factories;

use App\Models\Visitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Visitor>
 */
class VisitorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'total_visits' => fake()->numberBetween(1, 20),
            'provider' => fake()->randomElement(['PLDT', 'Globe Telecom', 'Converge ICT', 'Sky Broadband', null]),
        ];
    }
}
