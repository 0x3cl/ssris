<?php

namespace App\Services;

use App\Models\UlimsSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class UlimsClient
{
    private const APP = 'srris';

    private const TOKEN_CACHE_KEY = 'ulims.token';

    private const CATALOGUE_CACHE_TTL_MINUTES = 10;

    public function testCategories(): array
    {
        return $this->get('samples/getTestCategories', 'testCategories');
    }

    public function sampleTypes(): array
    {
        return $this->get('samples/getSampleTypes', 'sampleTypes');
    }

    public function testMethods(): array
    {
        return $this->get('samples/getTestMethods', 'testMethods');
    }

    /** @return array{ok: bool, message: string} */
    public function testConnection(): array
    {
        Cache::forget(self::TOKEN_CACHE_KEY);

        $setting = UlimsSetting::query()->first();

        if ($setting === null) {
            return ['ok' => false, 'message' => 'Save ULIMS settings before testing the connection.'];
        }

        $token = $this->login($setting);

        if ($token === null) {
            return ['ok' => false, 'message' => 'Could not log in to ULIMS. Check the base URL and username.'];
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$setting->base_url}/samples/getTestCategories");

            if (! $response->successful()) {
                return ['ok' => false, 'message' => "ULIMS responded with an error (HTTP {$response->status()})."];
            }

            $count = count($response->json('testCategories', []));

            return ['ok' => true, 'message' => "Connected to ULIMS. {$count} test categories are available."];
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => "Could not reach ULIMS: {$exception->getMessage()}"];
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function get(string $path, string $responseKey): array
    {
        $setting = UlimsSetting::query()->first();

        if ($setting === null) {
            return [];
        }

        $cached = Cache::get("ulims.{$responseKey}");

        if ($cached !== null) {
            return $cached;
        }

        $token = $this->login($setting);

        if ($token === null) {
            return [];
        }

        try {
            $response = Http::withToken($token)->timeout(10)->get("{$setting->base_url}/{$path}");

            if (! $response->successful()) {
                return [];
            }

            $records = $response->json($responseKey, []);
        } catch (Throwable) {
            return [];
        }

        if ($records !== []) {
            Cache::put("ulims.{$responseKey}", $records, now()->addMinutes(self::CATALOGUE_CACHE_TTL_MINUTES));
        }

        return $records;
    }

    private function login(UlimsSetting $setting): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addDays(25), function () use ($setting): ?string {
            try {
                $response = Http::asForm()->timeout(10)->post("{$setting->base_url}/login", [
                    'app' => self::APP,
                    'username' => $setting->username,
                ]);

                if (! $response->successful()) {
                    return null;
                }

                return $response->json('token');
            } catch (Throwable) {
                return null;
            }
        });
    }
}
