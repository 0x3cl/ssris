<?php

namespace App\Services;

use App\Enums\ServiceRequestLogAction;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use Illuminate\Support\Facades\Auth;

class ServiceRequestLogger
{
    public function log(
        ServiceRequest $serviceRequest,
        ServiceRequestLogAction $action,
        string $description,
        ?string $actorName = null,
    ): ServiceRequestLog {
        return $serviceRequest->logs()->create([
            'action' => $action,
            'description' => $description,
            'actor_name' => $actorName ?? Auth::user()?->name ?? 'System',
            'created_at' => now(),
        ]);
    }
}
