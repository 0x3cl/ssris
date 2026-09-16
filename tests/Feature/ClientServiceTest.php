<?php

namespace Tests\Feature;

use App\Enums\ClientService as ClientServiceType;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ClientServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_persists_client_and_casts_enums(): void
    {
        $data = Client::factory()->raw();

        $client = (new ClientService)->create($data + ['id' => 999]);

        $this->assertDatabaseHas('clients', ['id' => $client->id, 'fullname' => $data['fullname']]);
        $this->assertNotSame(999, $client->id);
        $this->assertSame(ClientServiceType::RddServices, $client->fresh()->service);
    }

    public function test_partial_update_preserves_other_fields_and_clears_nullable_fields(): void
    {
        $client = Client::factory()->create(['company' => 'Old company']);

        (new ClientService)->update($client->id, ['address' => 'Updated address', 'company' => null]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'firstname' => $client->firstname,
            'address' => 'Updated address',
            'company' => null,
        ]);
    }

    public function test_invalid_enum_values_are_rejected_without_saving(): void
    {
        $data = Client::factory()->raw();
        foreach (['type_client', 'source', 'service', 'business_role', 'enterprise_size', 'market'] as $field) {
            try {
                (new ClientService)->create(array_replace($data, [$field => 'invalid']));
                $this->fail('Expected validation failure for '.$field);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
        }

        $this->assertDatabaseCount('clients', 0);
    }

    public function test_clients_can_be_paginated_found_and_deleted(): void
    {
        $clients = Client::factory()->count(2)->create();
        $service = new ClientService;

        $page = $service->paginate(1);

        $this->assertSame(2, $page->total());
        $this->assertCount(1, $page->items());
        $this->assertTrue($clients->last()->is($page->items()[0]));
        $this->assertTrue($clients->first()->is($service->find($clients->first()->id)));
        $this->assertTrue($service->delete($clients->first()->id));
        $this->assertModelMissing($clients->first());
    }

    public function test_missing_client_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new ClientService)->find(999);
    }
}
