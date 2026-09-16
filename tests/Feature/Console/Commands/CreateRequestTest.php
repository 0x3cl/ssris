<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\ClientService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_walk_in_request_for_the_selected_service(): void
    {
        $this->artisan('create-request', [
            '--walkin' => true,
            '--rdd' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('service_requests', [
            'service' => ClientService::RddServices->value,
            'is_appointment' => false,
        ]);
        $this->assertDatabaseHas('clients', ['email' => 'iamcarlllemos@gmail.com']);
    }

    public function test_it_requires_one_request_type_and_service(): void
    {
        $this->artisan('create-request', ['--rdd' => true])
            ->expectsOutputToContain('Choose exactly one request type')
            ->assertFailed();
    }
}
