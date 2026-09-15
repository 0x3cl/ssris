<?php

namespace App\Models;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use Database\Factories\ServiceRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['status', 'is_appointment_approved', 'appointment_date', 'appointment_time'])]
class ServiceRequest extends Model
{
    /** @use HasFactory<ServiceRequestFactory> */
    use HasFactory;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function rddRequest(): HasOne
    {
        return $this->hasOne(RddRequest::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'appointment_date' => 'date',
            'appointment_time' => 'string',
            'is_appointment' => 'boolean',
            'is_appointment_approved' => 'boolean',
            'service' => ClientService::class,
            'status' => ServiceRequestStatus::class,
        ];
    }
}
