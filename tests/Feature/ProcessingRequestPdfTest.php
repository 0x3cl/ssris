<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Models\ProcessingRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProcessingRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_service_request_pdf_before_payment_is_verified(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::ProcessingServices, 'status' => ServiceRequestStatus::ForPayment]);
        $processingRequest = ProcessingRequest::factory()->create(['service_request_id' => $serviceRequest->id, 'op_no' => null, 'or_no' => null]);
        $processingRequest->items()->create(['item' => 'Sample test', 'weight' => '2.5 kg', 'quantity' => 1, 'unit_fee' => 100, 'total_fee' => 100]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/processing-request/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_download_the_service_request_pdf_after_payment_is_verified(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::ProcessingServices, 'status' => ServiceRequestStatus::AwaitingFeedback]);
        $processingRequest = ProcessingRequest::factory()->create(['service_request_id' => $serviceRequest->id, 'op_no' => '11', 'or_no' => '111']);
        $processingRequest->items()->create(['item' => 'Sample test', 'weight' => '2.5 kg', 'quantity' => 1, 'unit_fee' => 100, 'total_fee' => 100]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/processing-request/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
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
