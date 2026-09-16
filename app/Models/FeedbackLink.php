<?php

namespace App\Models;

use Database\Factories\FeedbackLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['service_request_id', 'token', 'expires_at', 'submitted_at'])]
class FeedbackLink extends Model
{
    /** @use HasFactory<FeedbackLinkFactory> */
    use HasFactory;

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(FeedbackResponse::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }
}
