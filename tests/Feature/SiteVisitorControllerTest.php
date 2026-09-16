<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteVisitorControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_visitors_with_stats_and_chart_series(): void
    {
        Visitor::factory()->create(['ip_address' => '10.0.0.1', 'provider' => 'PLDT', 'total_visits' => 5]);
        Visitor::factory()->create(['ip_address' => '10.0.0.2', 'provider' => 'Globe Telecom', 'total_visits' => 2]);

        $response = $this->actingAs($this->adminUser())
            ->get('/admin/site-visitors');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-visitors')
            ->has('visitors.data', 2)
            ->where('stats.totalVisitors', 2)
            ->where('stats.totalVisits', 7)
        );
    }

    public function test_index_filters_by_search(): void
    {
        Visitor::factory()->create(['ip_address' => '192.168.1.10', 'provider' => 'PLDT']);
        Visitor::factory()->create(['ip_address' => '192.168.1.20', 'provider' => 'Globe Telecom']);

        $response = $this->actingAs($this->adminUser())
            ->get('/admin/site-visitors?search=PLDT');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-visitors')
            ->has('visitors.data', 1)
            ->where('visitors.data.0.ip_address', '192.168.1.10')
        );
    }

    public function test_index_filters_by_specific_date(): void
    {
        $today = Visitor::factory()->create(['ip_address' => '172.16.0.1']);
        $lastMonth = Visitor::factory()->create(['ip_address' => '172.16.0.2']);
        $lastMonth->forceFill(['created_at' => now()->subMonth()])->save();

        $response = $this->actingAs($this->adminUser())
            ->get('/admin/site-visitors?date='.$today->created_at->toDateString());

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/site-visitors')
            ->has('visitors.data', 1)
            ->where('visitors.data.0.ip_address', '172.16.0.1')
        );
    }

    private function adminUser(): User
    {
        $permissions = collect(['site-visitors'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
