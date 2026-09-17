<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminPasswordResetController extends Controller
{
    private const SMTP_NOT_CONFIGURED_MESSAGE = 'Password reset is not available yet: this module needs SMTP to be configured by a superadmin before it can send reset links.';

    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('dashboard');
        }

        return Inertia::render('admin/forgot-password', [
            'smtpConfigured' => SmtpSetting::query()->exists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        $this->ensureSmtpIsConfigured();

        Password::broker('users')->sendResetLink(['email' => $data['email']]);

        // Always show the same message, whether or not that email has an account,
        // so this form can't be used to check which addresses are registered.
        return back()->with('success', 'If an account exists for that email address, a password reset link has been sent.');
    }

    public function edit(Request $request, string $token): Response|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('dashboard');
        }

        return Inertia::render('admin/reset-password', [
            'token' => $token,
            'email' => $request->string('email')->value(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('users')->reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        return to_route('admin.login')->with('success', 'Your password has been reset. Please sign in.');
    }

    private function ensureSmtpIsConfigured(): void
    {
        if (SmtpSetting::query()->doesntExist()) {
            throw ValidationException::withMessages(['email' => self::SMTP_NOT_CONFIGURED_MESSAGE]);
        }
    }
}
