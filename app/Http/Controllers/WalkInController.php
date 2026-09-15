<?php

namespace App\Http\Controllers;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Http\Requests\StoreWalkInRequest;
use App\Models\Client;
use App\Services\ClientService as ClientServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WalkInController extends Controller
{
    public function index(Request $request): Response
    {
        $selectedService = ClientService::tryFrom((string) $request->query('selected'));

        return Inertia::render('walk-in', [
            'selectedService' => $selectedService?->value,
            'services' => $this->options(ClientService::cases()),
            'clientTypes' => $this->options(ClientType::cases()),
            'businessRoles' => $this->options(ClientBusinessRole::cases()),
            'enterpriseSizes' => $this->options(ClientEnterpriseSize::cases()),
            'markets' => $this->options(ClientMarket::cases()),
            'sources' => $this->options(ClientSource::cases()),
        ]);
    }

    public function findClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $client = Client::query()
            ->where('email', $validated['email'])
            ->where('is_deleted', false)
            ->first();

        return response()->json([
            'client' => $client?->only([
                'firstname',
                'middlename',
                'lastname',
                'fullname',
                'email',
                'mobile_no',
                'fax_no',
                'age',
                'gender',
                'address',
                'region',
                'province',
                'municipality',
                'tel_no',
                'type_client',
                'company',
                'school_name',
                'business_role',
                'enterprise_size',
                'market',
                'products',
                'source',
                'service',
                'description',
            ]),
        ]);
    }

    public function store(StoreWalkInRequest $request, ClientServiceManager $clientService): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($clientService, $data): void {
            $existingClient = Client::query()->firstWhere('email', $data['email']);
            $client = $existingClient
                ? $clientService->update($existingClient->id, $data)
                : $clientService->create($data);

            DB::table('service_requests')->insert([
                'service' => $data['service'],
                'is_appointment' => false,
                'client_id' => $client->id,
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return to_route('walk-in')->with('success', 'Your walk-in request has been submitted.');
    }

    /**
     * @param  array<int, object>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function options(array $cases): array
    {
        return array_map(
            fn (object $case): array => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }
}
