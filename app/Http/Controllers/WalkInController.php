<?php

namespace App\Http\Controllers;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestLogAction;
use App\Http\Requests\StoreWalkInRequest;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Services\ClientService as ClientServiceManager;
use App\Services\FormTemplateMailer;
use App\Services\ServiceRequestLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class WalkInController extends Controller
{
    public function __construct(
        private readonly FormTemplateMailer $formTemplateMailer,
        private readonly ServiceRequestLogger $serviceRequestLogger,
    ) {}

    public function index(Request $request): Response
    {
        $selectedService = ClientService::tryFrom((string) $request->query('selected'));

        return Inertia::render('walk-in', [
            'selectedService' => $selectedService?->value,
            'selectedEmail' => filter_var($request->query('email'), FILTER_VALIDATE_EMAIL) ?: null,
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

        $client = Client::withTrashed()
            ->where('email', $validated['email'])

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

    public function validateDetails(StoreWalkInRequest $request): JsonResponse
    {
        $request->validated();

        return response()->json();
    }

    public function store(StoreWalkInRequest $request, ClientServiceManager $clientService): RedirectResponse
    {
        $data = $request->validated();
        $data['fullname'] = trim($data['firstname'].' '.$data['lastname']);

        $serviceRequest = DB::transaction(function () use ($clientService, $data): ServiceRequest {
            $existingClient = Client::withTrashed()->firstWhere('email', $data['email']);
            $client = $existingClient
                ? $clientService->updateReturningClient($existingClient, $data)
                : $clientService->create($data);

            return ServiceRequest::create([
                'service' => $data['service'],
                'is_appointment' => false,
                'client_id' => $client->id,
                'description' => $data['description'],
            ]);
        });

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::Created,
            'Walk-in service request submitted by the client.',
            $data['fullname'],
        );

        $emailQueued = $this->sendAcknowledgementEmail($serviceRequest, $data);

        $message = $emailQueued
            ? 'Your walk-in request has been submitted and a confirmation email has been queued.'
            : 'Your walk-in request has been submitted, but the email could not be queued.';

        return to_route('walk-in')->with('success', $message);
    }

    /** @param  array{email: string, fullname: string, mobile_no: string, service: string}  $data */
    private function sendAcknowledgementEmail(ServiceRequest $serviceRequest, array $data): bool
    {
        $error = $this->formTemplateMailer->send(FormTemplateKey::ServiceRequestReceipt, $data['email'], [
            'service' => ClientService::from($data['service'])->label(),
            'client_email' => $data['email'],
            'client_name' => $data['fullname'],
            'mobile_number' => $data['mobile_no'],
        ], $serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null
                ? 'Service request acknowledgement email queued for delivery to the client.'
                : "Service request acknowledgement email could not be queued: {$error}",
            'System',
        );

        return $error === null;
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
