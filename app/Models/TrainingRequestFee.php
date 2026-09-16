<?php

namespace App\Models;

use Database\Factories\TrainingRequestFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'training_request_id', 'reference_no', 'date_time', 'particulars', 'duration',
    'no_participants', 'net_amount_due', 'bill_no', 'or_no', 'bill_attachment', 'or_attachment',
])]
class TrainingRequestFee extends Model implements Auditable
{
    /** @use HasFactory<TrainingRequestFeeFactory> */
    use AuditableTrait, HasFactory;

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
            'no_participants' => 'integer',
            'net_amount_due' => 'float',
        ];
    }
}
