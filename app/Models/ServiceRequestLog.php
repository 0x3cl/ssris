<?php

namespace App\Models;

use App\Enums\ServiceRequestLogAction;
use Database\Factories\ServiceRequestLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['action', 'description', 'actor_name'])]
class ServiceRequestLog extends Model
{
    /** @use HasFactory<ServiceRequestLogFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'action' => ServiceRequestLogAction::class,
            'created_at' => 'datetime',
        ];
    }
}
