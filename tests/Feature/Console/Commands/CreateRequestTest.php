<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
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
        $this->assertDatabaseCount('tour_requests', 0);
        $this->assertDatabaseCount('tour_request_items', 0);
    }

    #[TestWith(['--walkin', false])]
    #[TestWith(['--appointment', true])]
    public function test_it_creates_complete_tour_requests(string $type, bool $isAppointment): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->setTime(9, 0));

        $this->artisan('create-request', [$type => true, '--tour' => true, '--amount' => 2])->assertSuccessful();

        $this->assertDatabaseCount('service_requests', 2);
        $this->assertDatabaseCount('tour_requests', 2);
        $this->assertDatabaseCount('tour_request_items', 2);
        $this->assertDatabaseCount('clients', 1);
        foreach (ServiceRequest::with(['tourRequest.items', 'logs'])->get() as $request) {
            $this->assertSame(ClientService::PlantTourServices, $request->service);
            $this->assertSame(ServiceRequestStatus::Pending, $request->status);
            $this->assertSame($isAppointment, $request->is_appointment);
            $this->assertSame($isAppointment ? '2026-09-24' : null, $request->appointment_date?->toDateString());
            $this->assertSame($isAppointment ? '09:00:00' : null, $request->appointment_time);
            $this->assertNull($request->description);
            $this->assertCount(1, $request->logs);

            $tour = $request->tourRequest;
            $this->assertSame('2026-09-24', $tour->visit_date->toDateString());
            $this->assertSame('09:00:00', $tour->visit_time);
            $this->assertSame(20, $tour->no_persons);
            $this->assertSame(2, $tour->no_groups);
            $this->assertNotEmpty($tour->message);
            $this->assertNotEmpty($tour->technology_assistance);
            $this->assertNotEmpty($tour->visit_objectives);
            $this->assertNull($tour->prepared_by);
            $this->assertNull($tour->noted_by);
            $this->assertNull($tour->conforme_primary);
            $this->assertNull($tour->conforme_secondary);
            $this->assertNull($tour->conforme_optional);
            $this->assertNull($tour->remarks);
            $this->assertCount(1, $tour->items);
            $this->assertSame(['physical', 'chemical'], $tour->items->first()->testing_lab);
            $this->assertSame(['spinning', 'weaving'], $tour->items->first()->pilot_plant);
            $this->assertSame(['tela-gallery'], $tour->items->first()->others);
        }
    }

    public function test_it_requires_one_request_type_and_service(): void
    {
        $this->artisan('create-request', ['--rdd' => true])
            ->expectsOutputToContain('Choose exactly one request type')
            ->assertFailed();
    }
}
