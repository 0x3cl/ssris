<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestLogAction;
use App\Models\ServiceRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_walk_in_request_logs_the_submission_first(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['is_appointment' => false]);

        $log = $serviceRequest->logs()->firstOrFail();

        $this->assertSame(ServiceRequestLogAction::Created, $log->action);
        $this->assertSame($serviceRequest->client->fullname, $log->actor_name);
    }

    public function test_creating_an_appointment_request_logs_the_submission_first(): void
    {
        $serviceRequest = ServiceRequest::factory()->create([
            'is_appointment' => true,
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '09:00:00',
        ]);

        $log = $serviceRequest->logs()->firstOrFail();

        $this->assertSame(ServiceRequestLogAction::Scheduled, $log->action);
        $this->assertSame($serviceRequest->client->fullname, $log->actor_name);
    }
}
