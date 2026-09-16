<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_the_training_request_and_moves_the_status_to_for_service_fee(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);

        $response = $this->actingAs($this->adminUser())
            ->post("/admin/requests/{$serviceRequest->id}/training-request", $this->trainingRequestPayload());

        $response->assertRedirect(route('admin.requests.training.fee.create', $serviceRequest));
        $serviceRequest->refresh();
        $this->assertSame(ServiceRequestStatus::ForServiceFee, $serviceRequest->status);
        $this->assertSame('Basic Weaving', $serviceRequest->trainingRequest->training_course_requested);
    }

    public function test_store_fee_creates_the_fee_and_moves_the_status_to_for_payment(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);
        $this->actingAs($admin = $this->adminUser())->post("/admin/requests/{$serviceRequest->id}/training-request", $this->trainingRequestPayload());

        $response = $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/training-request/fee", [
            'date_time' => now()->toDateTimeString(),
            'particulars' => 'Training services fee',
            'duration' => '3 days',
            'no_participants' => 25,
            'net_amount_due' => 1500,
        ]);

        $response->assertRedirect(route('admin.requests.training.payment.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'payment-verification']));
        $serviceRequest->refresh();
        $this->assertSame(ServiceRequestStatus::ForPayment, $serviceRequest->status);

        $fee = $serviceRequest->trainingRequest->fee;
        $this->assertNotNull($fee);
        $this->assertSame(1500.0, $fee->net_amount_due);
        $this->assertStringStartsWith('TRN-', $fee->reference_no);
    }

    public function test_update_payment_verifies_the_bill_and_or_numbers_and_moves_to_awaiting_feedback(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);
        $admin = $this->adminUser();
        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/training-request", $this->trainingRequestPayload());
        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/training-request/fee", [
            'date_time' => now()->toDateTimeString(),
            'particulars' => 'Training services fee',
            'duration' => '3 days',
            'no_participants' => 25,
            'net_amount_due' => 1500,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin.delete_challenge' => '1234'])
            ->post("/admin/requests/{$serviceRequest->id}/training-request/payment", [
                'bill_no' => 'BILL-001',
                'or_no' => 'OR-001',
                'confirmation_code' => '1234',
            ]);

        $response->assertRedirect(route('admin.requests.training.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback']));
        $serviceRequest->refresh();
        $this->assertSame(ServiceRequestStatus::AwaitingFeedback, $serviceRequest->status);
        $this->assertSame('OR-001', $serviceRequest->trainingRequest->fee->or_no);
    }

    public function test_update_payment_stores_optional_attachments_and_they_are_downloadable(): void
    {
        Storage::fake('local');
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);
        $admin = $this->adminUser();
        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/training-request", $this->trainingRequestPayload());
        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/training-request/fee", [
            'date_time' => now()->toDateTimeString(),
            'particulars' => 'Training services fee',
            'duration' => '3 days',
            'no_participants' => 25,
            'net_amount_due' => 1500,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['admin.delete_challenge' => '1234'])
            ->post("/admin/requests/{$serviceRequest->id}/training-request/payment", [
                'bill_no' => 'BILL-001',
                'or_no' => 'OR-001',
                'bill_attachment' => UploadedFile::fake()->create('bill.pdf', 100, 'application/pdf'),
                'or_attachment' => UploadedFile::fake()->create('or.pdf', 100, 'application/pdf'),
                'confirmation_code' => '1234',
            ]);

        $response->assertRedirect(route('admin.requests.training.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback']));
        $fee = $serviceRequest->trainingRequest->fresh()->fee;
        $this->assertNotNull($fee->bill_attachment);
        $this->assertNotNull($fee->or_attachment);
        Storage::disk('local')->assertExists($fee->bill_attachment);
        Storage::disk('local')->assertExists($fee->or_attachment);

        $this->actingAs($admin)
            ->get("/admin/requests/{$serviceRequest->id}/training-request/attachments/bill")
            ->assertOk();
        $this->actingAs($admin)
            ->get("/admin/requests/{$serviceRequest->id}/training-request/attachments/or")
            ->assertOk();
    }

    public function test_create_fee_is_blocked_until_the_training_request_is_for_service_fee(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/training-request/fee");

        $response->assertRedirect(route('admin.requests.index'));
    }

    public function test_create_renders_the_training_request_form(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::TrainingServices, 'status' => ServiceRequestStatus::Pending]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/training-request");

        $response->assertInertia(fn (Assert $page) => $page->component('admin/training-request-form'));
    }

    /** @return array<string, mixed> */
    private function trainingRequestPayload(): array
    {
        return [
            'training_course_requested' => 'Basic Weaving',
            'estimated_participants' => 30,
            'proposed_training_date' => now()->addWeek()->toDateString(),
            'proposed_training_venue' => 'PTRI Auditorium',
            'beneficiary_name' => 'Barangay Weavers Association',
            'purpose_of_training' => 'Upskill local weavers on basic weaving techniques.',
            'assigned_trainer' => 'Juan Dela Cruz',
            'assigned_assistant_trainer' => null,
            'official_course_title' => 'Basic Weaving Techniques',
            'approved_training_duration' => '3 days',
            'training_type' => 'in-house',
            'special_type_details' => null,
        ];
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
