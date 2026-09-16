<?php

namespace Database\Factories;

use App\Enums\ServiceRequestLogAction;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequestLog>
 */
class ServiceRequestLogFactory extends Factory
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
            'action' => ServiceRequestLogAction::StatusChanged,
            'description' => fake()->sentence(),
            'actor_name' => fake()->name(),
            'created_at' => now(),
        ];
    }
}
