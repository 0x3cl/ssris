<?php

namespace Database\Factories;

use App\Models\FeedbackLink;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeedbackLink>
 */
class FeedbackLinkFactory extends Factory
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
            'token' => Str::random(48),
            'expires_at' => now()->addHours(24),
            'submitted_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => ['expires_at' => now()->subHour()]);
    }

    public function submitted(): static
    {
        return $this->state(fn (): array => ['submitted_at' => now()]);
    }
}
