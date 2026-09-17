<?php

namespace App\Models;

use Database\Factories\TourRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'service_request_id',
    'visit_date',
    'visit_time',
    'supervisor_name',
    'message',
    'no_persons',
    'no_groups',
    'technology_assistance',
    'visit_objectives',
    'prepared_by',
    'noted_by',
    'conforme_primary',
    'conforme_secondary',
    'conforme_optional',
    'remarks',
])]
class TourRequest extends Model implements Auditable
{
    /** @use HasFactory<TourRequestFactory> */
    use AuditableTrait, HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TourRequestItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'no_persons' => 'integer',
            'no_groups' => 'integer',
        ];
    }
}
