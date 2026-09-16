<?php

namespace App\Services;

use App\Models\FeedbackLink;
use App\Models\ServiceRequest;
use Illuminate\Support\Str;

class FeedbackLinkService
{
    /**
     * Generate a new feedback form link for the given service request, valid for 24 hours.
     * Multiple links may exist for the same service request.
     */
    public function generate(ServiceRequest $serviceRequest): FeedbackLink
    {
        return FeedbackLink::create([
            'service_request_id' => $serviceRequest->id,
            'token' => $this->uniqueToken(),
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function findValid(string $token): ?FeedbackLink
    {
        $link = FeedbackLink::query()->where('token', $token)->first();

        if ($link === null || $link->isExpired() || $link->isSubmitted()) {
            return null;
        }

        return $link;
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(48);
        } while (FeedbackLink::query()->where('token', $token)->exists());

        return $token;
    }
}
