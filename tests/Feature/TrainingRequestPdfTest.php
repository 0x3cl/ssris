<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\TrainingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TrainingRequestPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_the_service_request_pdf(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['status' => ServiceRequestStatus::ForServiceFee]);
        TrainingRequest::factory()->create(['service_request_id' => $serviceRequest->id]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/training-request/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_download_the_service_fee_pdf(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['status' => ServiceRequestStatus::ForPayment]);
        $trainingRequest = TrainingRequest::factory()->create(['service_request_id' => $serviceRequest->id]);
        $trainingRequest->fee()->create([
            'reference_no' => 'TRN-AB-00001',
            'date_time' => now(),
            'particulars' => 'Sample training',
            'duration' => '2 day(s)',
            'no_participants' => 20,
            'net_amount_due' => 1500,
        ]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/training-request/fee/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_service_fee_pdf_returns_error_when_fee_is_not_available(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['status' => ServiceRequestStatus::ForServiceFee]);
        TrainingRequest::factory()->create(['service_request_id' => $serviceRequest->id]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/training-request/fee/pdf");

        $response->assertRedirect(route('admin.requests.index'));
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
