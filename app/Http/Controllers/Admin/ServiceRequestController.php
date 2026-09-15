<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
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
                'type' => $serviceRequest->is_appointment ? 'appointment' : 'walk-in',
                'status' => $serviceRequest->status,
                'appointment_date' => $serviceRequest->appointment_date?->toDateString(),
                'appointment_time' => $serviceRequest->appointment_time,
                'description' => $serviceRequest->description,
                'created_at' => $serviceRequest->created_at->toDateTimeString(),
                'client' => $serviceRequest->client,
            ]);

        return Inertia::render('admin/requests', [
            'filters' => compact('entries', 'search', 'status', 'type'),
            'requests' => $requests,
            'statuses' => ['pending', 'on-going', 'payment', 'completed', 'cancelled'],
        ]);
    }
}
