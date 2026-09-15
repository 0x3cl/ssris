<?php

namespace App\Http\Controllers;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Client;
use App\Services\ClientService as ClientServiceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('appointment', [
            'services' => $this->options(ClientService::cases()),
            'clientTypes' => $this->options(ClientType::cases()),
            'businessRoles' => $this->options(ClientBusinessRole::cases()),
            'enterpriseSizes' => $this->options(ClientEnterpriseSize::cases()),
            'markets' => $this->options(ClientMarket::cases()),
            'sources' => $this->options(ClientSource::cases()),
        ]);
    }

    public function validateBooking(Request $request): JsonResponse
    {
        $request->validate([
            'appointment_date' => ['required', 'date', 'after:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
        ]);

        return response()->json();
    }

    public function findClient(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $client = Client::query()->where('email', $validated['email'])->where('is_deleted', false)->first();

        return response()->json(['client' => $client?->only([
            'firstname', 'middlename', 'lastname', 'fullname', 'email', 'mobile_no', 'fax_no', 'age', 'gender',
            'address', 'region', 'province', 'municipality', 'tel_no', 'type_client', 'company', 'school_name',
            'business_role', 'enterprise_size', 'market', 'products', 'source', 'service', 'description',
        ])]);
    }

    public function validateDetails(StoreAppointmentRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json();
    }

    public function store(StoreAppointmentRequest $request, ClientServiceManager $clientService): RedirectResponse
    {
        $data = $request->validated();
        $data['fullname'] = trim($data['firstname'].' '.$data['lastname']);

        DB::transaction(function () use ($clientService, $data): void {
            $existingClient = Client::query()->firstWhere('email', $data['email']);
            $client = $existingClient
                ? $clientService->update($existingClient->id, $data)
                : $clientService->create($data);

            DB::table('service_requests')->insert([
                'service' => $data['service'],
                'is_appointment' => true,
                'client_id' => $client->id,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'description' => $data['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return to_route('appointment')->with('success', 'Your appointment request has been submitted.');
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
