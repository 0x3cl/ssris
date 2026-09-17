<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\FormTemplateKey;
use App\Services\FormTemplateMailer;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'password', 'account_status', 'role_type', 'profile_image'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements Auditable
{
    /** @use HasFactory<UserFactory> */
    use AuditableTrait, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** @var array<int, string> */
    protected $auditExclude = ['password', 'remember_token'];

    /** @return HasMany<UserService, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(UserService::class);
    }

    /**
     * Send the password reset link through the app's SMTP-configured, queued
     * mail pipeline (the same one used for every other admin notice) instead
     * of Laravel's default notification mailer, so it goes out through
     * whichever SMTP settings the admin has configured, on the queue.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $resetUrl = route('admin.password.reset', ['token' => $token, 'email' => $this->email]);
        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);

        $error = app(FormTemplateMailer::class)->send(FormTemplateKey::PasswordReset, $this->email, [
            'name' => $this->name,
            'reset_url' => $resetUrl,
            'expires' => $expiresInMinutes === 1 ? '1 minute' : "{$expiresInMinutes} minutes",
        ]);

        if ($error !== null) {
            report(new \RuntimeException("Password reset email for {$this->email} could not be queued: {$error}"));
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
