<?php

namespace App\Models;

use Database\Factories\RddRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['rdd_request_id', 'item', 'specification', 'quantity', 'unit_fee', 'total_fee'])]
class RddRequestItem extends Model implements Auditable
{
    /** @use HasFactory<RddRequestItemFactory> */
    use AuditableTrait, HasFactory;

    public function rddRequest(): BelongsTo
    {
        return $this->belongsTo(RddRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_fee' => 'float',
            'total_fee' => 'float',
        ];
    }
}
