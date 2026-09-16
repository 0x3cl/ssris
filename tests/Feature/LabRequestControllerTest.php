<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Models\LabRequestItem;
use App\Models\ServiceRequest;
use App\Models\UlimsSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LabRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_renders_the_ulims_catalogue_as_page_props(): void
    {
        $this->fakeUlims();
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::LabServices, 'status' => ServiceRequestStatus::Pending]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/lab-request");

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/lab-request-form')
            ->has('testCategories', 1)
            ->has('sampleTypes', 1)
            ->has('testMethods', 1)
        );
    }

    public function test_store_persists_the_selected_ulims_ids(): void
    {
        $this->fakeUlims();
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::LabServices, 'status' => ServiceRequestStatus::Pending]);

        $response = $this->actingAs($this->adminUser())
            ->post("/admin/requests/{$serviceRequest->id}/lab-request", [
                'test_category' => 'Textiles',
                'ulims_test_category_id' => 1,
                'sample_type' => 'Fabric',
                'ulims_sample_type_id' => 2,
                'due_date' => now()->addWeek()->toDateString(),
                'discount_percentage' => 0,
                'items' => [
                    ['test' => 'Tensile strength', 'ulims_test_id' => 12, 'method' => 'Room temperature', 'quantity' => 1, 'unit_fee' => 350],
                ],
            ]);

        $response->assertRedirect();
        $serviceRequest->refresh();
        $this->assertSame(ServiceRequestStatus::ForPayment, $serviceRequest->status);

        $labRequest = $serviceRequest->labRequest;
        $this->assertSame(1, $labRequest->ulims_test_category_id);
        $this->assertSame(2, $labRequest->ulims_sample_type_id);

        $item = $labRequest->items->first();
        $this->assertInstanceOf(LabRequestItem::class, $item);
        $this->assertSame(12, $item->ulims_test_id);
    }

    private function fakeUlims(): void
    {
        UlimsSetting::query()->create(['base_url' => 'http://ulims.test/api', 'username' => 'srris']);

        Http::fake([
            'ulims.test/api/login' => Http::response(['token' => 'test-token']),
            'ulims.test/api/samples/getTestCategories' => Http::response(['testCategories' => [['id' => 1, 'categoryName' => 'Textiles']]]),
            'ulims.test/api/samples/getSampleTypes' => Http::response(['sampleTypes' => [['id' => 2, 'sampleType' => 'Fabric', 'testCategoryId' => 1]]]),
            'ulims.test/api/samples/getTestMethods' => Http::response(['testMethods' => [['id' => 12, 'testName' => 'Tensile strength', 'method' => 'ASTM D123', 'fee' => 350, 'sampleType' => 2]]]),
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
