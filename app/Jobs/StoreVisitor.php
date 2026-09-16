<?php

namespace App\Jobs;

use App\Models\Visitor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class StoreVisitor implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ip,
        public ?string $userAgent,
    ) {}

    public function handle(): void
    {
        $provider = null;

        try {
            $response = Http::timeout(2)
                ->get("https://ipinfo.io/{$this->ip}/json");

            if ($response->successful()) {
                $provider = $response->json('org');
            }
        } catch (\Throwable $e) {
            // Ignore provider lookup errors
        }

        $visitor = Visitor::firstOrCreate(
            ['ip_address' => $this->ip],
            [
                'user_agent' => $this->userAgent,
                'total_visits' => 0,
                'provider' => $provider,
            ]
        );

        $visitor->update([
            'user_agent' => $this->userAgent,
            'provider' => $provider,
            'total_visits' => $visitor->total_visits + 1,
        ]);
    }
}
