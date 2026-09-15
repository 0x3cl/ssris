<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WalkInControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_walk_in_page_preserves_the_selected_service(): void
    {
        $response = $this->get('/walk-in?selected='.ClientService::RddServices->value);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('walk-in')
            ->where('selectedService', ClientService::RddServices->value)
            ->has('services', count(ClientService::cases())),
        );
    }

    public function test_client_lookup_returns_the_existing_client(): void
    {
        $client = Client::factory()->create(['email' => 'returning@example.com']);

        $response = $this->getJson('/walk-in/client?email=returning@example.com');

        $response->assertOk();
        $response->assertJsonPath('client.email', $client->email);
        $response->assertJsonPath('client.firstname', $client->firstname);
    }

    public function test_client_lookup_returns_null_when_the_email_is_not_registered(): void
    {
        $response = $this->getJson('/walk-in/client?email=new@example.com');

        $response->assertOk();
        $response->assertExactJson(['client' => null]);
    }

    public function test_client_lookup_requires_a_valid_email_address(): void
    {
        $response = $this->getJson('/walk-in/client?email=not-an-email');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    public function test_walk_in_submission_creates_a_client_and_service_request(): void
    {
        $data = Client::factory()->raw([
            'email' => 'walk-in@example.com',
            'service' => ClientService::RddServices->value,
        ]);

        $response = $this->post('/walk-in', $data);

        $response->assertRedirect('/walk-in');
        $this->assertDatabaseHas('clients', ['email' => 'walk-in@example.com']);
        $this->assertDatabaseHas('service_requests', [
            'service' => ClientService::RddServices->value,
            'is_appointment' => false,
        ]);
    }
}
