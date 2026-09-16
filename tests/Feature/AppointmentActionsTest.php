<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppointmentActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_appointment_requires_a_reason(): void
    {
        $serviceRequest = $this->appointmentRequest();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->patch("/admin/requests/{$serviceRequest->id}/cancel-appointment", ['confirmation_code' => '1234']);

        $response->assertSessionHasErrors('reason');
        $this->assertSame(ServiceRequestStatus::Pending, $serviceRequest->fresh()->status);
    }

    public function test_cancelling_an_appointment_with_a_reason_logs_it(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $serviceRequest = $this->appointmentRequest();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->patch("/admin/requests/{$serviceRequest->id}/cancel-appointment", [
                'reason' => 'Client requested a different date entirely.',
                'confirmation_code' => '1234',
            ]);

        $response->assertRedirect();
        $this->assertSame(ServiceRequestStatus::Cancelled, $serviceRequest->fresh()->status);

        $log = $serviceRequest->logs()->where('action', ServiceRequestLogAction::AppointmentCancelled)->firstOrFail();
        $this->assertStringContainsString('Client requested a different date entirely.', $log->description);
    }

    public function test_confirming_without_rescheduling_does_not_require_a_reason(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $serviceRequest = $this->appointmentRequest();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->patch("/admin/requests/{$serviceRequest->id}/approve-appointment", ['confirmation_code' => '1234']);

        $response->assertSessionDoesntHaveErrors('reason');
        $this->assertTrue($serviceRequest->fresh()->is_appointment_approved);
    }

    public function test_rescheduling_an_appointment_requires_a_reason(): void
    {
        $serviceRequest = $this->appointmentRequest();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->patch("/admin/requests/{$serviceRequest->id}/approve-appointment", [
                'reschedule' => true,
                'appointment_date' => now()->addWeek()->toDateString(),
                'appointment_time' => '10:00',
                'confirmation_code' => '1234',
            ]);

        $response->assertSessionHasErrors('reason');
        $this->assertFalse($serviceRequest->fresh()->is_appointment_approved);
    }

    public function test_rescheduling_an_appointment_with_a_reason_logs_it(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $serviceRequest = $this->appointmentRequest();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->patch("/admin/requests/{$serviceRequest->id}/approve-appointment", [
                'reschedule' => true,
                'appointment_date' => now()->addWeek()->toDateString(),
                'appointment_time' => '10:00',
                'reason' => 'Receiving officer unavailable on the original date.',
                'confirmation_code' => '1234',
            ]);

        $response->assertRedirect();
        $this->assertTrue($serviceRequest->fresh()->is_appointment_approved);

        $log = $serviceRequest->logs()->where('action', ServiceRequestLogAction::AppointmentRescheduled)->firstOrFail();
        $this->assertStringContainsString('Receiving officer unavailable on the original date.', $log->description);
    }

    private function appointmentRequest(): ServiceRequest
    {
        return ServiceRequest::factory()->appointment()->create([
            'status' => ServiceRequestStatus::Pending,
            'is_appointment_approved' => false,
        ]);
    }

    private function adminUser(): User
    {
        $permissions = collect(['requests'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
