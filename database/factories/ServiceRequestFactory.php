<?php

namespace Database\Factories;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service' => ClientService::RddServices,
            'is_appointment' => false,
            'client_id' => Client::factory(),
            'status' => ServiceRequestStatus::Pending,
            'description' => fake()->sentence(),
        ];
    }

    public function appointment(): static
    {
        return $this->state(fn (): array => [
            'is_appointment' => true,
            'appointment_date' => fake()->dateTimeBetween('+1 day', '+2 weeks')->format('Y-m-d'),
            'appointment_time' => '09:00:00',
        ]);
    }
}
