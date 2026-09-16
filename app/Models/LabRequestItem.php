<?php

namespace App\Models;

use Database\Factories\LabRequestItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['lab_request_id', 'test', 'ulims_test_id', 'method', 'quantity', 'unit_fee', 'total_fee'])]
class LabRequestItem extends Model
{
    /** @use HasFactory<LabRequestItemFactory> */
    use HasFactory;

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
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
