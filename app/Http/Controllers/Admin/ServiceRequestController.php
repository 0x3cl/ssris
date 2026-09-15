<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    public function index(Request $request): Response
    {
        $entries = (int) $request->integer('entries', 10);
        $entries = in_array($entries, [10, 25, 50], true) ? $entries : 10;
        $type = $request->string('type')->value();
        $status = $request->string('status')->value();
        $search = trim($request->string('search')->value());

        $requests = ServiceRequest::query()
            ->with('client:id,firstname,middlename,lastname,fullname,email,mobile_no,type_client')
            ->when($type === 'walk-in', fn ($query) => $query->where('is_appointment', false))
            ->when($type === 'appointment', fn ($query) => $query->where('is_appointment', true))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
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
            'filters' => compact('entries', 'search', 'status', 'type'),
            'requests' => $requests,
            'statuses' => array_map(
                fn (ServiceRequestStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                ServiceRequestStatus::cases(),
            ),
        ]);
    }

    public function proceed(ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->status === ServiceRequestStatus::Pending, 422, 'Only pending requests can proceed.');
        abort_if($serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved, 422, 'Confirm the appointment before proceeding.');

        $serviceRequest->update(['status' => ServiceRequestStatus::ForPayment]);

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
        ]);

        $this->validateConfirmationCode($request);

        $update = ['is_appointment_approved' => true];

        if ($request->boolean('reschedule')) {
            $update['appointment_date'] = $data['appointment_date'];
            $update['appointment_time'] = $data['appointment_time'];
        }

        $serviceRequest->update($update);

        return back()->with('success', $request->boolean('reschedule')
            ? 'Appointment rescheduled and confirmed.'
            : 'Appointment confirmed.');
    }

    public function cancelAppointment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        abort_unless($serviceRequest->is_appointment, 422, 'Only appointment requests can be cancelled here.');
        abort_unless($serviceRequest->status === ServiceRequestStatus::Pending, 422, 'Only pending requests can be cancelled here.');

        $this->validateConfirmationCode($request);

        $serviceRequest->update(['status' => ServiceRequestStatus::Cancelled]);

        return back()->with('success', 'The appointment request has been cancelled.');
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
