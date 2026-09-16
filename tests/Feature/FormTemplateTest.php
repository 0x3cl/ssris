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

        $this->assertSame(7, FormTemplate::query()->count());

        $feedbackReminder = FormTemplate::query()->where('key', FormTemplateKey::FeedbackReminder)->firstOrFail();
        $this->assertSame('Feedback Follow-up', $feedbackReminder->name);
        $this->assertSame('SRIS: Feedback Form Reminder', $feedbackReminder->subject);
        $this->assertStringContainsString('ready for pick-up', $feedbackReminder->body);
        $this->assertStringContainsString('{{reference}}', $feedbackReminder->body);
        $this->assertStringContainsString('{{due_date}}', $feedbackReminder->body);
        $this->assertStringContainsString('{{link}}', $feedbackReminder->body);
        $this->assertContains('link', $feedbackReminder->variables);
        $this->assertStringContainsString('Receiving Officer', $feedbackReminder->body);
        // The feedback link is generated when the reminder is sent, not a static placeholder,
        // and sits on its own line below the "form link:" label.
        $this->assertStringContainsString('<p>Customer Satisfaction Feedback form link:</p><p>{{link}}</p>', $feedbackReminder->body);
        $this->assertStringNotContainsString('PTRI RDD Receiving Officer', $feedbackReminder->body);
        // A blank line separates the greeting from the body...
        $this->assertStringContainsString('<p>Good Day!</p><p><br></p><p>Please be advised', $feedbackReminder->body);
        // The reference-number sentence keeps its own line break, matching the source copy.
        $this->assertStringContainsString('<p>Please be advised that your service request with Reference Number: {{reference}}</p><p>due on {{due_date}} is ready for pick-up.</p>', $feedbackReminder->body);
        // ...and the body from the sign-off, whose two lines sit adjacent with no gap.
        $this->assertStringContainsString('Thank you so much.</p><p><br></p><p>Sincerely yours,</p><p>Receiving Officer</p>', $feedbackReminder->body);
        // The feedback link sits in its own paragraph block, set off by blank lines.
        $this->assertStringContainsString('{{link}}</p><p><br></p><p>Thank you so much.</p>', $feedbackReminder->body);

        $reschedule = FormTemplate::query()->where('key', FormTemplateKey::AppointmentReschedule)->firstOrFail();
        $this->assertSame('Appointment Rescheduling', $reschedule->name);
        $this->assertSame('SRIS: Re-scheduled Appointment', $reschedule->subject);
        foreach (['previous_date', 'previous_time', 'new_date', 'new_time', 'service', 'client_email', 'client_name', 'mobile_number', 'reason'] as $variable) {
            $this->assertContains($variable, $reschedule->variables);
            $this->assertStringContainsString("{{{$variable}}}", $reschedule->body);
        }

        $confirmed = FormTemplate::query()->where('key', FormTemplateKey::AppointmentConfirmed)->firstOrFail();
        $this->assertSame('Appointment Confirmation', $confirmed->name);
        $this->assertSame('SRIS: Confirmed Appointment', $confirmed->subject);
        foreach (['appointment_id', 'confirmed_date', 'confirmed_time', 'service', 'client_email', 'client_name', 'mobile_number'] as $variable) {
            $this->assertContains($variable, $confirmed->variables);
            $this->assertStringContainsString("{{{$variable}}}", $confirmed->body);
        }

        $cancellation = FormTemplate::query()->where('key', FormTemplateKey::AppointmentCancellation)->firstOrFail();
        $this->assertSame('Appointment Cancellation', $cancellation->name);
        $this->assertSame('SRIS: Appointment Cancelled', $cancellation->subject);
        foreach (['appointment_id', 'service', 'appointment_date', 'appointment_time', 'client_email', 'client_name', 'administrator', 'reason'] as $variable) {
            $this->assertContains($variable, $cancellation->variables);
            $this->assertStringContainsString("{{{$variable}}}", $cancellation->body);
        }

        $receipt = FormTemplate::query()->where('key', FormTemplateKey::ServiceRequestReceipt)->firstOrFail();
        $this->assertSame('Service Request Confirmation', $receipt->name);
        $this->assertSame('SRIS: Service Request', $receipt->subject);
        $this->assertStringContainsString('<ul>', $receipt->body);
        $this->assertStringContainsString('<li>Email: {{client_email}}</li>', $receipt->body);
        foreach (['service', 'client_email', 'client_name', 'mobile_number'] as $variable) {
            $this->assertContains($variable, $receipt->variables);
            $this->assertStringContainsString("{{{$variable}}}", $receipt->body);
        }

        $testNotification = FormTemplate::query()->where('key', FormTemplateKey::TestNotification)->firstOrFail();
        $this->assertSame('Test Notification', $testNotification->name);
        $this->assertStringContainsString('{{sent_at}}', $testNotification->body);

        foreach (FormTemplateKey::cases() as $key) {
            $this->assertTrue(FormTemplate::query()->where('key', $key)->exists());
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $this->seed(FormTemplateSeeder::class);

        $this->assertSame(7, FormTemplate::query()->count());
    }

    public function test_admin_can_view_the_form_templates_index(): void
    {
        $this->seed(FormTemplateSeeder::class);
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get('/admin/form-templates');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/form-templates')
            ->has('templates', 7),
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
