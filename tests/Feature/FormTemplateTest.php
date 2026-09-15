<?php

namespace Tests\Feature;

use App\Enums\FormTemplateKey;
use App\Models\FormTemplate;
use App\Models\User;
use Database\Seeders\FormTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_the_default_form_templates(): void
    {
        $this->seed(FormTemplateSeeder::class);

        $this->assertSame(5, FormTemplate::query()->count());

        $feedbackReminder = FormTemplate::query()->where('key', FormTemplateKey::FeedbackReminder)->firstOrFail();
        $this->assertStringContainsString('ready for pick-up', $feedbackReminder->body);
        $this->assertStringContainsString('{{reference_no}}', $feedbackReminder->body);
        $this->assertStringContainsString('{{due}}', $feedbackReminder->body);
        $this->assertStringContainsString('{{link}}', $feedbackReminder->body);

        $reschedule = FormTemplate::query()->where('key', FormTemplateKey::AppointmentReschedule)->firstOrFail();
        $this->assertStringContainsString('{{old_schedule}}', $reschedule->body);
        $this->assertStringContainsString('{{new_schedule}}', $reschedule->body);

        foreach (FormTemplateKey::cases() as $key) {
            $this->assertTrue(FormTemplate::query()->where('key', $key)->exists());
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $this->seed(FormTemplateSeeder::class);

        $this->assertSame(5, FormTemplate::query()->count());
    }

    public function test_admin_can_view_the_form_templates_index(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/form-templates');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/form-templates')
            ->has('templates', 5),
        );
    }

    public function test_admin_can_update_a_form_template(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $admin = $this->adminUser();
        $template = FormTemplate::query()->where('key', FormTemplateKey::PaymentReminder)->firstOrFail();

        $response = $this->actingAs($admin)->put("/admin/form-templates/{$template->id}", [
            'subject' => 'Updated subject',
            'body' => '<p>Updated body with <strong>{{reference_no}}</strong>.</p>',
        ]);

        $response->assertRedirect('/admin/form-templates');
        $this->assertSame('Updated subject', $template->fresh()->subject);
        $this->assertSame('<p>Updated body with <strong>{{reference_no}}</strong>.</p>', $template->fresh()->body);
    }

    public function test_updating_a_form_template_strips_dangerous_markup(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $admin = $this->adminUser();
        $template = FormTemplate::query()->where('key', FormTemplateKey::PaymentReminder)->firstOrFail();

        $response = $this->actingAs($admin)->put("/admin/form-templates/{$template->id}", [
            'subject' => 'Updated <script>alert(1)</script>subject',
            'body' => '<p onclick="alert(1)">Safe text</p><script>alert(1)</script><img src=x onerror=alert(1)>',
        ]);

        $response->assertRedirect('/admin/form-templates');
        $template->refresh();
        $this->assertSame('Updated alert(1)subject', $template->subject);
        $this->assertStringNotContainsString('<script', $template->subject);
        $this->assertSame('<p>Safe text</p>', $template->body);
    }

    public function test_updating_a_form_template_rejects_a_message_left_with_no_visible_text(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $admin = $this->adminUser();
        $template = FormTemplate::query()->where('key', FormTemplateKey::PaymentReminder)->firstOrFail();

        $response = $this->actingAs($admin)->put("/admin/form-templates/{$template->id}", [
            'subject' => 'Updated subject',
            'body' => '<script>alert(1)</script>',
        ]);

        $response->assertSessionHasErrors('body');
        $this->assertNotSame('', $template->fresh()->body);
    }

    public function test_render_replaces_placeholders_with_given_values(): void
    {
        $template = FormTemplate::factory()->create([
            'subject' => 'Hello {{name}}',
            'body' => 'Reference Number: {{reference_no}} due on {{due}}.',
        ]);

        $rendered = $template->render([
            'name' => 'Juan',
            'reference_no' => 'RDD-0001',
            'due' => '2026-09-30',
        ]);

        $this->assertSame('Hello Juan', $rendered['subject']);
        $this->assertSame('Reference Number: RDD-0001 due on 2026-09-30.', $rendered['body']);
    }

    public function test_render_html_escapes_values_but_leaves_the_subject_plain(): void
    {
        $template = FormTemplate::factory()->create([
            'subject' => 'Hello {{name}}',
            'body' => '<p>Hello {{name}}</p>',
        ]);

        $rendered = $template->render(['name' => '<b>Juan</b>']);

        $this->assertSame('Hello <b>Juan</b>', $rendered['subject']);
        $this->assertSame('<p>Hello &lt;b&gt;Juan&lt;/b&gt;</p>', $rendered['body']);
    }

    private function adminUser(): User
    {
        $permissions = collect(['form-templates'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
