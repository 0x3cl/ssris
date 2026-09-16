<?php

namespace App\Models;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'service', 'is_appointment', 'client_id', 'description',
    'status', 'is_appointment_approved', 'appointment_date', 'appointment_time', 'reschedule_date', 'reschedule_time',
])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function rddRequest(): HasOne
    {
        return $this->hasOne(RddRequest::class);
    }

    public function processingRequest(): HasOne
    {
        return $this->hasOne(ProcessingRequest::class);
    }

    public function labRequest(): HasOne
    {
        return $this->hasOne(LabRequest::class);
    }

    public function logs(): HasMany
    {
        // Tiebreak on id, since several actions log more than one entry within the same second.
        return $this->hasMany(ServiceRequestLog::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function feedbackLinks(): HasMany
    {
        return $this->hasMany(FeedbackLink::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'string',
            'reschedule_date' => 'date',
            'reschedule_time' => 'string',
            'is_appointment' => 'boolean',
            'is_appointment_approved' => 'boolean',
            'service' => ClientService::class,
            'status' => ServiceRequestStatus::class,
        ];
    }
}
