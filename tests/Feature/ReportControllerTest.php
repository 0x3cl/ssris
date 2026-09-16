<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Enums\ClientType;
use App\Models\Client;
use App\Models\FeedbackLink;
use App\Models\FeedbackResponse;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_demographics_tab_scopes_clients_to_the_admins_assigned_services(): void
    {
        Client::factory()->create(['service' => ClientService::RddServices, 'gender' => 'female']);
        Client::factory()->create(['service' => ClientService::RddServices, 'gender' => 'male']);
        Client::factory()->create(['service' => ClientService::LabServices]);

        $response = $this->actingAs($this->adminUser([ClientService::RddServices]))
            ->get('/admin/reports?tab=demographics');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('tab', 'demographics')
            ->where('reportData.total', 2)
        );
    }

    public function test_clients_tab_lists_and_filters_by_type(): void
    {
        Client::factory()->create(['service' => ClientService::RddServices, 'type_client' => ClientType::Individual]);
        Client::factory()->create(['service' => ClientService::RddServices, 'type_client' => ClientType::Government]);

        $response = $this->actingAs($this->adminUser([ClientService::RddServices]))
            ->get('/admin/reports?tab=clients&type_client=government');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->has('reportData.clients.data', 1)
        );
    }

    public function test_service_requests_tab_reports_status_breakdown(): void
    {
        ServiceRequest::factory()->create(['service' => ClientService::RddServices]);
        ServiceRequest::factory()->create(['service' => ClientService::LabServices]);

        $response = $this->actingAs($this->adminUser([ClientService::RddServices]))
            ->get('/admin/reports?tab=service-requests');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('reportData.stats.total', 1)
        );
    }

    public function test_feedback_tab_computes_overall_weighted_score(): void
    {
        $serviceRequest = ServiceRequest::factory()->create(['service' => ClientService::RddServices]);
        $link = FeedbackLink::factory()->submitted()->create(['service_request_id' => $serviceRequest->id]);

        FeedbackResponse::factory()->create([
            'feedback_link_id' => $link->id,
            'ratings' => ['1' => '5'],
            'snapshot' => [
                'dimensions' => [
                    ['id' => 1, 'name' => 'Responsiveness', 'items' => [
                        ['id' => 1, 'description' => 'Prompt response'],
                    ]],
                ],
                'ratings' => [
                    ['id' => 1, 'name' => 'Excellent', 'value' => '5', 'weight' => 5],
                ],
            ],
        ]);

        $response = $this->actingAs($this->adminUser([ClientService::RddServices]))
            ->get('/admin/reports?tab=feedback');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('reportData.stats.overallScore', 5.0)
            ->where('reportData.stats.interpretation', 'Excellent')
        );
    }

    public function test_site_visitors_tab_is_not_scoped_by_assigned_service(): void
    {
        Visitor::factory()->create(['ip_address' => '10.0.0.1']);

        $response = $this->actingAs($this->adminUser([ClientService::LabServices]))
            ->get('/admin/reports?tab=site-visitors');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('reportData.stats.totalVisitors', 1)
        );
    }

    public function test_audit_trails_tab_loads(): void
    {
        $response = $this->actingAs($this->adminUser([ClientService::LabServices]))
            ->get('/admin/reports?tab=audit-trails');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->has('reportData.audits')
        );
    }

    public function test_quarter_period_narrows_results_to_the_selected_quarter(): void
    {
        $admin = $this->adminUser([ClientService::RddServices]);

        $inQuarter = Client::factory()->create(['service' => ClientService::RddServices]);
        $inQuarter->forceFill(['created_at' => '2026-02-15'])->save();

        $outsideQuarter = Client::factory()->create(['service' => ClientService::RddServices]);
        $outsideQuarter->forceFill(['created_at' => '2026-07-15'])->save();

        $response = $this->actingAs($admin)
            ->get('/admin/reports?tab=demographics&period=quarter&year=2026&quarter=1');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.total', 1)
            ->where('period.label', 'Q1 2026')
        );
    }

    public function test_year_period_narrows_results_to_the_selected_year(): void
    {
        $admin = $this->adminUser([ClientService::RddServices]);

        $thisYear = Client::factory()->create(['service' => ClientService::RddServices]);
        $thisYear->forceFill(['created_at' => '2026-05-01'])->save();

        $lastYear = Client::factory()->create(['service' => ClientService::RddServices]);
        $lastYear->forceFill(['created_at' => '2025-05-01'])->save();

        $response = $this->actingAs($admin)
            ->get('/admin/reports?tab=demographics&period=year&year=2025');

        $response->assertInertia(fn (Assert $page) => $page
            ->where('reportData.total', 1)
        );
    }

    /** @param array<int, ClientService> $services */
    private function adminUser(array $services = []): User
    {
        $permissions = collect(['reports'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        foreach ($services as $service) {
            $user->services()->create(['service' => $service]);
        }

        return $user;
    }
}
