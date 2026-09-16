<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditTrailControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // PHPUnit runs as a CLI process, so Laravel reports runningInConsole() as true;
        // laravel-auditing skips auditing in that context unless explicitly re-enabled here.
        config(['audit.console' => true]);
    }

    public function test_index_lists_audits_for_created_and_updated_events(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin);

        $client = Client::factory()->create();
        $client->update(['firstname' => 'Updated']);

        $response = $this->get('/admin/audit-trails');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-trails')
            ->has('audits.data', 2)
            ->where('audits.data.0.event', 'updated')
            ->where('audits.data.1.event', 'created')
        );
    }

    public function test_index_filters_by_event(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin);

        $client = Client::factory()->create();
        $client->update(['firstname' => 'Updated']);

        $response = $this->get('/admin/audit-trails?event=created');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-trails')
            ->has('audits.data', 1)
            ->where('audits.data.0.event', 'created')
        );
    }

    public function test_index_filters_by_search_matching_user_name(): void
    {
        $admin = $this->adminUser();
        $admin->forceFill(['name' => 'Jane Auditor'])->save();

        $this->actingAs($admin);

        Client::factory()->create();

        $response = $this->get('/admin/audit-trails?search=Jane');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-trails')
            ->has('audits.data', 1)
            ->where('audits.data.0.user', 'Jane Auditor')
        );
    }

    public function test_login_and_logout_are_recorded_with_the_acting_user(): void
    {
        $admin = $this->adminUser();
        $admin->forceFill(['username' => 'auditor', 'password' => 'password'])->save();

        $this->post('/admin/login', ['username' => 'auditor', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->actingAs($admin)->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $response = $this->actingAs($admin)->get('/admin/audit-trails');

        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-trails')
            ->has('audits.data', 2)
            ->where('audits.data.0.event', 'logout')
            ->where('audits.data.0.user', $admin->name)
            ->where('audits.data.1.event', 'login')
            ->where('audits.data.1.user', $admin->name)
        );
    }

    private function adminUser(): User
    {
        $permissions = collect(['audit-trails'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
