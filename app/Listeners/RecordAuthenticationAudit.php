<?php

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class RecordAuthenticationAudit
{
    public function __construct(private readonly Request $request) {}

    public function handleLogin(Login $event): void
    {
        $this->record('login', $event->user);
    }

    public function handleLogout(Logout $event): void
    {
        $this->record('logout', $event->user);
    }

    private function record(string $event, mixed $user): void
    {
        if (! $user instanceof User) {
            return;
        }

        Audit::query()->create([
            'event' => $event,
            'auditable_type' => $user->getMorphClass(),
            'auditable_id' => $user->getKey(),
            'user_type' => $user->getMorphClass(),
            'user_id' => $user->getKey(),
            'url' => $this->request->fullUrl(),
            'ip_address' => $this->request->ip(),
            'user_agent' => mb_substr((string) $this->request->userAgent(), 0, 1023),
        ]);
    }
}
