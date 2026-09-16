<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use App\Services\FormTemplateMailer;
use App\Services\ServiceRequestLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function __construct(
        private readonly FormTemplateMailer $formTemplateMailer,
        private readonly ServiceRequestLogger $serviceRequestLogger,
    ) {}

    public function index(Request $request): Response
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $type = $request->string('type')->value();
        $status = $request->string('status')->value();
        $service = $request->string('service')->value();
        $date = $request->string('date')->value();
        $search = trim($request->string('search')->value());

        $assignedServices = (Auth::user()?->services ?? collect())->pluck('service')->map(fn (ClientService $service): string => $service->value)->all();

        $requests = ServiceRequest::query()
            ->whereIn('service', $assignedServices)
            ->with(['client:id,firstname,middlename,lastname,fullname,email,mobile_no,type_client'])
            ->when($type === 'walk-in', fn ($query) => $query->where('is_appointment', false))
            ->when($type === 'appointment', fn ($query) => $query->where('is_appointment', true))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($service !== '', fn ($query) => $query->where('service', $service))
            ->when($date !== '', fn ($query) => $query->whereDate('created_at', $date))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($requestQuery) use ($search): void {
                    $requestQuery->where('description', 'like', "%{$search}%")
                        ->orWhere('service', 'like', "%{$search}%")
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery->where('fullname', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($entries)
            ->withQueryString()
            ->through(fn (ServiceRequest $serviceRequest): array => [
                'id' => $serviceRequest->id,
                'service' => $serviceRequest->service->label(),
                'service_value' => $serviceRequest->service->value,
                'type' => $serviceRequest->is_appointment ? 'appointment' : 'walk-in',
                'status' => $serviceRequest->status->label(),
                'status_value' => $serviceRequest->status->value,
                'appointment_date' => $serviceRequest->appointment_date?->toDateString(),
                'appointment_time' => $serviceRequest->appointment_time,
                'is_appointment_approved' => $serviceRequest->is_appointment_approved,
                'description' => $serviceRequest->description,
                'created_at' => $serviceRequest->created_at->format('F, d Y H:i:s'),
                'client' => $serviceRequest->client,
            ]);

        return Inertia::render('admin/requests', [
            'filters' => compact('entries', 'search', 'status', 'service', 'type', 'date'),
            'requests' => $requests,
            'statuses' => array_map(
                fn (ServiceRequestStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ServiceRequestStatus::cases(),
            ),
            'services' => array_map(
                fn (ClientService $service): array => ['value' => $service->value, 'label' => $service->label()],
                ClientService::cases(),
            ),
        ]);
    }

    public function logs(ServiceRequest $serviceRequest): JsonResponse
    {
        return response()->json([
            'logs' => $serviceRequest->logs->map(fn (ServiceRequestLog $log): array => [
                'id' => $log->id,
                'action' => $log->action->label(),
                'description' => $log->description,
                'actor_name' => $log->actor_name,
                'created_at' => $log->created_at?->format('F d, Y H:i:s'),
            ]),
        ]);
    }

    public function proceed(ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->status === ServiceRequestStatus::Pending, 422, 'Only pending requests can proceed.');
        abort_if($serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved, 422, 'Confirm the appointment before proceeding.');

        $serviceRequest->update(['status' => ServiceRequestStatus::ForPayment]);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::StatusChanged,
            'Request moved from pending to for payment.',
        );

        return back()->with('success', 'The request has moved to for payment.');
    }

    public function approveAppointment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->is_appointment, 422, 'Only appointment requests can be confirmed.');
        abort_unless($serviceRequest->status === ServiceRequestStatus::Pending, 422, 'Only pending requests can be confirmed.');

        $data = $request->validate([
            'reschedule' => ['sometimes', 'boolean'],
            'appointment_date' => ['required_if:reschedule,true', 'date', 'after_or_equal:today'],
            'appointment_time' => ['required_if:reschedule,true', 'date_format:H:i,H:i:s'],
            'reason' => ['required_if:reschedule,true', 'nullable', 'string', 'max:1000'],
        ]);

        $this->validateConfirmationCode($request);

        $update = ['is_appointment_approved' => true];

        if ($request->boolean('reschedule')) {
            $update['reschedule_date'] = $serviceRequest->appointment_date;
            $update['reschedule_time'] = $serviceRequest->appointment_time;
            $update['appointment_date'] = $data['appointment_date'];
            $update['appointment_time'] = $data['appointment_time'];
        }

        $serviceRequest->update($update);
        $serviceRequest->load('client');

        if ($request->boolean('reschedule')) {
            $this->serviceRequestLogger->log(
                $serviceRequest,
                ServiceRequestLogAction::AppointmentRescheduled,
                "Appointment rescheduled from {$this->formatDate($serviceRequest->reschedule_date)} {$this->formatTime($serviceRequest->reschedule_time)} to {$this->formatDate($serviceRequest->appointment_date)} {$this->formatTime($serviceRequest->appointment_time)} and confirmed. Reason: {$data['reason']}",
            );

            $emailQueued = $this->sendTemplatedEmail($serviceRequest, FormTemplateKey::AppointmentReschedule, [
                'previous_date' => $this->formatDate($serviceRequest->reschedule_date),
                'previous_time' => $this->formatTime($serviceRequest->reschedule_time),
                'new_date' => $this->formatDate($serviceRequest->appointment_date),
                'new_time' => $this->formatTime($serviceRequest->appointment_time),
                'service' => $serviceRequest->service->label(),
                'client_email' => $serviceRequest->client->email,
                'client_name' => $serviceRequest->client->fullname,
                'mobile_number' => $serviceRequest->client->mobile_no,
                'reason' => $data['reason'],
            ]);

            $message = 'Appointment rescheduled and confirmed';
        } else {
            $this->serviceRequestLogger->log(
                $serviceRequest,
                ServiceRequestLogAction::AppointmentConfirmed,
                "Appointment confirmed for {$this->formatDate($serviceRequest->appointment_date)} {$this->formatTime($serviceRequest->appointment_time)}.",
            );

            $emailQueued = $this->sendTemplatedEmail($serviceRequest, FormTemplateKey::AppointmentConfirmed, [
                'appointment_id' => $serviceRequest->id,
                'confirmed_date' => $this->formatDate($serviceRequest->appointment_date),
                'confirmed_time' => $this->formatTime($serviceRequest->appointment_time),
                'service' => $serviceRequest->service->label(),
                'client_email' => $serviceRequest->client->email,
                'client_name' => $serviceRequest->client->fullname,
                'mobile_number' => $serviceRequest->client->mobile_no,
            ]);

            $message = 'Appointment confirmed';
        }

        return back()->with('success', $this->withEmailStatus($message, $emailQueued));
    }

    public function cancelAppointment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->is_appointment, 422, 'Only appointment requests can be cancelled here.');
        abort_unless($serviceRequest->status === ServiceRequestStatus::Pending, 422, 'Only pending requests can be cancelled here.');

        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);

        $this->validateConfirmationCode($request);

        $serviceRequest->update(['status' => ServiceRequestStatus::Cancelled]);
        $serviceRequest->load('client');

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::AppointmentCancelled,
            "Appointment for {$this->formatDate($serviceRequest->appointment_date)} {$this->formatTime($serviceRequest->appointment_time)} cancelled. Reason: {$data['reason']}",
        );

        $emailQueued = $this->sendTemplatedEmail($serviceRequest, FormTemplateKey::AppointmentCancellation, [
            'appointment_id' => $serviceRequest->id,
            'service' => $serviceRequest->service->label(),
            'appointment_date' => $this->formatDate($serviceRequest->appointment_date),
            'appointment_time' => $this->formatTime($serviceRequest->appointment_time),
            'client_email' => $serviceRequest->client->email,
            'client_name' => $serviceRequest->client->fullname,
            'administrator' => Auth::user()?->name ?? 'Administrator',
            'reason' => $data['reason'],
        ]);

        return back()->with('success', $this->withEmailStatus('The appointment request has been cancelled', $emailQueued));
    }

    /** @param  array<string, string|int|float|null>  $values */
    private function sendTemplatedEmail(ServiceRequest $serviceRequest, FormTemplateKey $key, array $values): bool
    {
        $error = $this->formTemplateMailer->send($key, $serviceRequest->client->email, $values, $serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null
                ? "\"{$key->label()}\" email queued for delivery to the client."
                : "\"{$key->label()}\" email could not be queued: {$error}",
        );

        return $error === null;
    }

    private function withEmailStatus(string $message, bool $emailQueued): string
    {
        return $emailQueued ? "{$message} and the email has been queued for delivery." : "{$message}, but the email could not be queued.";
    }

    private function formatDate(?Carbon $date): string
    {
        return $date?->format('F j, Y') ?? '';
    }

    private function formatTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return Carbon::parse($time)->format('g:i A');
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
}
