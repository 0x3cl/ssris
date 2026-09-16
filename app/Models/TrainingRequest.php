<?php

namespace App\Models;

use Database\Factories\TrainingRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'service_request_id', 'training_course_requested', 'estimated_participants',
    'proposed_training_date', 'proposed_training_venue', 'beneficiary_name', 'purpose_of_training',
    'assigned_trainer', 'assigned_assistant_trainer', 'official_course_title',
    'approved_training_duration', 'training_type', 'special_type_details',
])]
class TrainingRequest extends Model implements Auditable
{
    /** @use HasFactory<TrainingRequestFactory> */
    use AuditableTrait, HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function fee(): HasOne
    {
        return $this->hasOne(TrainingRequestFee::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'estimated_participants' => 'integer',
            'proposed_training_date' => 'date',
        ];
    }
}
