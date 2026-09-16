<?php

namespace App\Http\Controllers;

use App\Services\PsgcClient;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(private readonly PsgcClient $psgc) {}

    public function regions(): JsonResponse
    {
        return response()->json(['regions' => $this->psgc->regions()]);
    }

    public function provinces(string $region): JsonResponse
    {
        $provinces = $this->psgc->provincesForRegion($region);

        return response()->json([
            'provinces' => $provinces,
            'municipalities' => $provinces === [] ? $this->psgc->municipalitiesForRegion($region) : [],
        ]);
    }

    public function municipalities(string $province): JsonResponse
    {
        return response()->json(['municipalities' => $this->psgc->municipalitiesForProvince($province)]);
    }
}
