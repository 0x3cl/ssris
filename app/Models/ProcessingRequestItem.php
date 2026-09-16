<?php

namespace App\Models;

use Database\Factories\ProcessingRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['processing_request_id', 'item', 'weight', 'quantity', 'unit_fee', 'total_fee'])]
class ProcessingRequestItem extends Model
{
    /** @use HasFactory<ProcessingRequestItemFactory> */
    use HasFactory;

    public function processingRequest(): BelongsTo
    {
        return $this->belongsTo(ProcessingRequest::class);
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
