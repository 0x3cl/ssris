<?php

namespace App\Models;

use Database\Factories\RddRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['service_request_id', 'reference_no', 'due_date', 'sub_total', 'discount', 'total_fee', 'op_no', 'or_no', 'op_attachment', 'or_attachment'])]
class RddRequest extends Model implements Auditable
{
    /** @use HasFactory<RddRequestFactory> */
    use AuditableTrait, HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RddRequestItem::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'sub_total' => 'float',
            'discount' => 'float',
            'total_fee' => 'float',
        ];
    }
}
