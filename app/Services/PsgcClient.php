<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Fetches Philippine Standard Geographic Code (PSGC) region, province, and
 * city/municipality data from the public PSGC API, caching each response
 * since the reference data changes rarely.
 */
class PsgcClient
{
    private const BASE_URL = 'https://psgc.gitlab.io/api';

    private const CACHE_TTL_DAYS = 30;

    /** @return array<int, array{code: string, name: string}> */
    public function regions(): array
    {
        return $this->get('regions.all', '/regions/');
    }

    /** @return array<int, array{code: string, name: string}> */
    public function provincesForRegion(string $regionCode): array
    {
        return $this->get("region.{$regionCode}.provinces", "/regions/{$regionCode}/provinces/");
    }

    /** @return array<int, array{code: string, name: string}> */
    public function municipalitiesForProvince(string $provinceCode): array
    {
        return $this->get("province.{$provinceCode}.municipalities", "/provinces/{$provinceCode}/cities-municipalities/");
    }

    /**
     * Some regions (e.g. NCR) have no provinces and list their
     * cities/municipalities directly under the region.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public function municipalitiesForRegion(string $regionCode): array
    {
        return $this->get("region.{$regionCode}.municipalities", "/regions/{$regionCode}/cities-municipalities/");
    }

    /** @return array<int, array{code: string, name: string}> */
    private function get(string $cacheKey, string $path): array
    {
        $key = "psgc.{$cacheKey}";
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::timeout(10)->get(self::BASE_URL.$path);

            if (! $response->successful()) {
                return [];
            }

            $records = collect($response->json())
                ->map(fn (array $item): array => ['code' => $item['code'], 'name' => $this->normalizeName($item['name'])])
                ->sortBy('name')
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }

        if ($records !== []) {
            Cache::put($key, $records, now()->addDays(self::CACHE_TTL_DAYS));
        }

        return $records;
    }

    /** PSGC labels most cities "City of {Name}"; display it the more common way, "{Name} City". */
    private function normalizeName(string $name): string
    {
        if (str_starts_with($name, 'City of ')) {
            return mb_substr($name, mb_strlen('City of ')).' City';
        }

        return $name;
    }
}
