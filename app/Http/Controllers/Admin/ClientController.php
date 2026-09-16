<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function requests(Request $request): Response
    {
        $data = $request->validate(['client_id' => ['required', 'integer']]);
        $client = Client::withTrashed()->findOrFail($data['client_id']);
        $requests = ServiceRequest::query()->where('client_id', $client->id)
            ->latest('id')->paginate(12)->withQueryString()
            ->through(fn (ServiceRequest $item): array => [
                'id' => $item->id,
                'service' => $item->service->label(),
                'status' => $item->status->label(),
                'description' => $item->description,
                'date' => $item->created_at->format('F j, Y'),
                'type' => $item->is_appointment ? 'Appointment' : 'Walk-in',
                'url' => $this->serviceRequestUrl($item),
            ]);

        return Inertia::render('admin/client-requests', [
            'client' => $client->only(['id', 'fullname', 'email']),
            'requests' => $requests,
        ]);
    }

    /**
     * Link to whichever stage of the request is actually reachable right now,
     * instead of a fixed step (e.g. "feedback") the request may not have reached yet.
     */
    private function serviceRequestUrl(ServiceRequest $item): string
    {
        $prefix = match ($item->service) {
            ClientService::RddServices => 'rdd',
            ClientService::LabServices => 'lab',
            ClientService::ProcessingServices => 'processing',
            default => null,
        };

        if ($prefix === null) {
            return route('admin.requests.client-detail', ['serviceRequest' => $item->id, 'service' => $item->service->value]);
        }

        return match (true) {
            $item->status === ServiceRequestStatus::Pending && (! $item->is_appointment || $item->is_appointment_approved) => route("admin.requests.{$prefix}.create", $item),
            $item->status === ServiceRequestStatus::ForPayment => route("admin.requests.{$prefix}.payment.edit", ['serviceRequest' => $item, 'tab' => 'payment-verification']),
            in_array($item->status, [ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed], true) => route("admin.requests.{$prefix}.feedback.edit", ['serviceRequest' => $item, 'tab' => 'feedback']),
            default => route('admin.requests.index'),
        };
    }

    public function showRequest(ServiceRequest $serviceRequest, string $service): Response
    {
        abort_unless($serviceRequest->service->value === $service, 404);
        $serviceRequest->load('client');

        return Inertia::render('admin/client-request-detail', [
            'client' => $serviceRequest->client->only(['id', 'fullname', 'email']),
            'request' => [
                'id' => $serviceRequest->id,
                'service' => $serviceRequest->service->label(),
                'status' => $serviceRequest->status->label(),
                'description' => $serviceRequest->description,
                'date' => $serviceRequest->created_at->format('F j, Y'),
                'type' => $serviceRequest->is_appointment ? 'Appointment' : 'Walk-in',
            ],
        ]);
    }

    public function archive(Request $request, Client $client): RedirectResponse
    {
        $this->validateConfirmationCode($request);

        $client->delete();

        return back()->with('success', 'Client archived. Request history has been preserved.');
    }

    public function restore(Request $request, int $client): RedirectResponse
    {
        $this->validateConfirmationCode($request);

        app(\App\Services\ClientService::class)->restore($client);

        return back()->with('success', 'Client restored successfully.');
    }

    private function validateConfirmationCode(Request $request): void
    {
        $data = $request->validate(['confirmation_code' => ['required', 'digits:4']]);
        $challenge = $request->session()->get('admin.delete_challenge');

        if (! is_string($challenge) || ! hash_equals($challenge, $data['confirmation_code'])) {
            throw ValidationException::withMessages(['confirmation_code' => 'Enter the displayed four-digit confirmation code to confirm this action.']);
        }

        $request->session()->forget('admin.delete_challenge');
    }

    public function index(Request $request): Response
    {
        $entries = $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $search = trim($request->string('search')->toString());
        $status = $request->input('status') === 'archived' ? 'archived' : 'active';
        $clients = Client::query()->when($status === 'archived', fn ($query) => $query->onlyTrashed())
            ->select(['id', 'fullname', 'email', 'mobile_no', 'type_client', 'company', 'school_name', 'deleted_at'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    foreach (['fullname', 'email', 'mobile_no', 'company', 'school_name'] as $column) {
                        $query->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($entries)
            ->withQueryString()
            ->through(fn (Client $client): array => [
                'id' => $client->id,
                'requests_url' => route('admin.clients.requests', ['client_id' => $client->id]),
                'fullname' => $client->fullname,
                'email' => $client->email,
                'mobile_no' => $client->mobile_no,
                'type' => $client->type_client?->label(),
                'organization' => $client->company ?: $client->school_name,
                'archived' => $client->trashed(),
            ]);

        return Inertia::render('admin/clients', [
            'clients' => $clients,
            'filters' => compact('entries', 'search', 'status'),
        ]);
    }
}
