<?php

namespace App\Models;

use Database\Factories\LabRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['service_request_id', 'quotation_no', 'due_date', 'test_category', 'ulims_test_category_id', 'sample_type', 'ulims_sample_type_id', 'sub_total', 'discount', 'total_fee', 'op_no', 'or_no', 'op_attachment', 'or_attachment'])]
class LabRequest extends Model
{
    /** @use HasFactory<LabRequestFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class);
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
