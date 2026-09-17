<?php

namespace Tests\Feature;

use App\Jobs\SendQueuedServiceMail;
use App\Models\FormTemplate;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_reports_whether_smtp_is_configured(): void
    {
        $this->get('/admin/forgot-password')
            ->assertInertia(fn (Assert $page) => $page->component('admin/forgot-password')->where('smtpConfigured', false));

        $this->seedSmtpSetting();

        $this->get('/admin/forgot-password')
            ->assertInertia(fn (Assert $page) => $page->where('smtpConfigured', true));
    }

    public function test_requesting_a_reset_link_queues_the_password_reset_email(): void
    {
        Queue::fake();
        $this->seedSmtpSetting();
        $this->seedPasswordResetTemplate();
        $user = User::factory()->create(['email' => 'admin@example.test']);

        $this->post('/admin/forgot-password', ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertPushed(SendQueuedServiceMail::class, fn (SendQueuedServiceMail $job): bool => $job->recipientEmail === $user->email
            && $job->mailable->renderedSubject === 'SRIS: Reset Your Password');
    }

    public function test_requesting_a_reset_link_for_an_unknown_email_gives_the_same_response(): void
    {
        Queue::fake();
        $this->seedSmtpSetting();
        $this->seedPasswordResetTemplate();

        $this->post('/admin/forgot-password', ['email' => 'nobody@example.test'])
            ->assertRedirect()
            ->assertSessionHas('success');

        Queue::assertNothingPushed();
    }

    public function test_requesting_a_reset_link_is_blocked_when_smtp_is_not_configured(): void
    {
        Queue::fake();
        $user = User::factory()->create(['email' => 'admin@example.test']);

        $this->post('/admin/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');

        Queue::assertNothingPushed();
    }

    public function test_reset_password_page_renders_with_the_token_and_email(): void
    {
        $this->get('/admin/reset-password/some-token?email=admin@example.test')
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/reset-password')
                ->where('token', 'some-token')
                ->where('email', 'admin@example.test'));
    }

    public function test_a_valid_token_resets_the_password(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'password' => Hash::make('OldPassword123')]);
        $token = Password::broker('users')->createToken($user);

        $this->post('/admin/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertRedirect('/admin/login')->assertSessionHas('success');

        $this->assertTrue(Hash::check('NewPassword456', $user->refresh()->password));
    }

    public function test_an_invalid_token_does_not_reset_the_password(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.test', 'password' => Hash::make('OldPassword123')]);

        $this->post('/admin/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('OldPassword123', $user->refresh()->password));
    }

    private function seedPasswordResetTemplate(): void
    {
        FormTemplate::factory()->create([
            'key' => 'password-reset',
            'subject' => 'SRIS: Reset Your Password',
            'body' => '<p>Hi {{name}}, reset here: {{reset_url}} (expires in {{expires}})</p>',
            'variables' => ['name', 'reset_url', 'expires'],
        ]);
    }

    private function seedSmtpSetting(): void
    {
        SmtpSetting::query()->create([
            'host' => 'smtp.example.test',
            'port' => '587',
            'username' => 'user@example.test',
            'password' => 'secret',
        ]);
    }
}
