<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\OtherTourFacility;
use App\Enums\PilotPlantFacility;
use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Enums\TestingLabFacility;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelTourRequest;
use App\Http\Requests\StoreTourSignatoriesRequest;
use App\Http\Requests\UpdateTourScheduleRequest;
use App\Models\Client;
use App\Models\FeedbackDisplaySetting;
use App\Models\FeedbackLink;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use App\Models\TourRequest;
use App\Services\FeedbackLinkService;
use App\Services\FeedbackPdfService;
use App\Services\FormTemplateMailer;
use App\Services\PlantTourConfirmationPdfService;
use App\Services\PlantTourRequestPdfService;
use App\Services\ServiceRequestLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TourRequestController extends Controller
{
    public function __construct(
        private readonly ServiceRequestLogger $serviceRequestLogger,
        private readonly FeedbackLinkService $feedbackLinks,
        private readonly FormTemplateMailer $formTemplateMailer,
    ) {}

    public function create(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->tourRequestAccessError($serviceRequest, $serviceRequest->status)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'tourRequest.items']);

        return Inertia::render('admin/tour-request-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'tourRequest' => $this->tourRequestPayload($serviceRequest->tourRequest),
            'signatoryOptions' => config('tour.signatories'),
            'today' => now()->toDateString(),
            'canWrite' => Auth::user()?->can('requests.write') ?? false,
            'actionUrls' => [
                'accept' => route('admin.requests.tour.accept', $serviceRequest),
                'cancel' => route('admin.requests.tour.cancel', $serviceRequest),
                'reschedule' => route('admin.requests.tour.reschedule', $serviceRequest),
                'signatories' => route('admin.requests.tour.signatories', $serviceRequest),
                'requestPdf' => route('admin.requests.tour.request.pdf', $serviceRequest),
                'confirmationPdf' => route('admin.requests.tour.confirmation.pdf', $serviceRequest),
                'markDone' => route('admin.requests.tour.mark-done', $serviceRequest),
            ],
        ]);
    }

    public function accept(ServiceRequest $serviceRequest): RedirectResponse
    {
        DB::transaction(function () use ($serviceRequest): void {
            $this->lockPendingTour($serviceRequest);
            $serviceRequest->update(['status' => ServiceRequestStatus::AssignSignatories]);
            $this->serviceRequestLogger->log($serviceRequest, ServiceRequestLogAction::StatusChanged, 'Tour accepted. The request is now ready to assign signatories.');
        });

        return to_route('admin.requests.tour.create', ['serviceRequest' => $serviceRequest, 'tab' => 'signatories'])
            ->with('success', $this->notifyClient($serviceRequest, FormTemplateKey::TourAccepted, 'Tour accepted'));
    }

    public function cancel(CancelTourRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $reason = $request->validated('reason');
        DB::transaction(function () use ($serviceRequest, $reason): void {
            $this->lockPendingTour($serviceRequest);
            $serviceRequest->update(['status' => ServiceRequestStatus::Cancelled]);
            $this->serviceRequestLogger->log($serviceRequest, ServiceRequestLogAction::StatusChanged, "Tour cancelled. Reason: {$reason}");
        });

        return to_route('admin.requests.index')->with('success', $this->notifyClient($serviceRequest, FormTemplateKey::TourCancelled, 'Tour cancelled', ['reason' => $reason]));
    }

    public function reschedule(UpdateTourScheduleRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        $data = $request->validated();
        $previous = DB::transaction(function () use ($serviceRequest, $data): array {
            $this->lockPendingTour($serviceRequest);
            $tour = $serviceRequest->tourRequest;
            $previous = ['previous_date' => $tour->visit_date?->format('F j, Y'), 'previous_time' => $this->formatTime($tour->visit_time)];
            $tour->update(['visit_date' => $data['visit_date'], 'visit_time' => $data['visit_time']]);
            if ($serviceRequest->is_appointment) {
                $serviceRequest->update([
                    'reschedule_date' => $serviceRequest->appointment_date,
                    'reschedule_time' => $serviceRequest->appointment_time,
                    'appointment_date' => $data['visit_date'],
                    'appointment_time' => $data['visit_time'],
                ]);
            }
            $this->serviceRequestLogger->log($serviceRequest, ServiceRequestLogAction::Scheduled,
                "Tour rescheduled from {$previous['previous_date']} {$previous['previous_time']} to {$data['visit_date']} {$data['visit_time']}. Reason: {$data['reason']}");

            return $previous;
        });

        return back()->with('success', $this->notifyClient($serviceRequest, FormTemplateKey::TourRescheduled, 'New tour schedule proposed', [...$previous, 'reason' => $data['reason']]));
    }

    public function storeSignatories(StoreTourSignatoriesRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        DB::transaction(function () use ($request, $serviceRequest): void {
            $serviceRequest->setRawAttributes(ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id)->getAttributes(), true);
            abort_if($error = $this->tourRequestAccessError($serviceRequest, ServiceRequestStatus::AssignSignatories), 422, $error ?? '');
            $serviceRequest->tourRequest()->firstOrFail()->update($request->validated());
            $serviceRequest->update(['status' => ServiceRequestStatus::ForSignature]);
            $this->serviceRequestLogger->log($serviceRequest, ServiceRequestLogAction::StatusChanged, 'Tour signatories submitted. The request is now for signature.');
        });

        return to_route('admin.requests.tour.create', ['serviceRequest' => $serviceRequest, 'tab' => 'for-signature'])
            ->with('success', 'Tour signatories submitted. The request is now for signature.');
    }

    public function markDone(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->tourRequestAccessError($serviceRequest, ServiceRequestStatus::ForSignature)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        DB::transaction(function () use ($serviceRequest): void {
            $serviceRequest->update(['status' => ServiceRequestStatus::AwaitingFeedback]);
            $this->serviceRequestLogger->log($serviceRequest, ServiceRequestLogAction::StatusChanged, 'Tour marked as done. The request is now awaiting feedback.');
        });

        $message = $this->sendFeedbackNotice($serviceRequest, 'Tour marked as done');

        return to_route('admin.requests.tour.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback'])
            ->with('success', $message);
    }

    public function sendFeedbackReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->tourRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        return back()->with('success', $this->sendFeedbackNotice($serviceRequest, 'Feedback reminder sent'));
    }

    private function sendFeedbackNotice(ServiceRequest $serviceRequest, string $message): string
    {
        $serviceRequest->load(['client', 'tourRequest']);
        $link = $this->feedbackLinks->generate($serviceRequest);

        $error = $this->formTemplateMailer->send(FormTemplateKey::FeedbackReminder, $serviceRequest->client->email, [
            'reference' => 'SR-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT),
            'due_date' => $serviceRequest->tourRequest->visit_date?->format('F j, Y') ?? now()->format('F j, Y'),
            'link' => url("/feedback/{$link->token}"),
        ], $serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null
                ? "Feedback reminder email queued for delivery to the client, with a form link valid until {$link->expires_at->format('F j, Y g:i A')}."
                : "Feedback reminder email could not be queued: {$error}",
        );

        return $error === null ? "{$message}. Feedback reminder email queued for delivery." : "{$message}, but the feedback email could not be queued: {$error}";
    }

    public function downloadRequestPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($error = $this->tourRequestAccessError($serviceRequest, $serviceRequest->status)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'tourRequest.items']);
        $document = app(PlantTourRequestPdfService::class)->render([
            'created_at' => $serviceRequest->created_at->format('Y-m-d H:i:s'),
            'client' => $this->clientPayload($serviceRequest->client),
            'tour' => $this->tourRequestPayload($serviceRequest->tourRequest),
        ]);

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"plant-tour-request-{$serviceRequest->id}.pdf\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function downloadConfirmationPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        $allowedStatuses = [ServiceRequestStatus::ForSignature, ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed];

        if ($error = $this->tourRequestAccessError($serviceRequest, $allowedStatuses)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'tourRequest.items']);
        $tour = $this->tourRequestPayload($serviceRequest->tourRequest);
        $document = app(PlantTourConfirmationPdfService::class)->render([
            'reference_no' => now()->format('y').'-'.str_pad((string) $serviceRequest->id, 3, '0', STR_PAD_LEFT).'-TOUR',
            'date' => now()->format('F j, Y'),
            'recipient' => $serviceRequest->client->fullname,
            'requesting_party' => $serviceRequest->client->company ?? $serviceRequest->client->school_name ?? $serviceRequest->client->fullname,
            'visit_date_time' => trim($tour['visit_date'].' '.$tour['visit_time']),
            'facilities' => implode(', ', array_filter([...$tour['testing_lab'], ...$tour['pilot_plant'], ...$tour['others']])) ?: '—',
            'purpose' => $tour['visit_objectives'] ?: $tour['technology_assistance'] ?: '—',
            'prepared_by' => $tour['prepared_by'] ?? '—',
            'noted_by' => $tour['noted_by'] ?? '—',
            'approved_by' => $tour['conforme_secondary'] ?? $tour['conforme_primary'] ?? '—',
        ]);

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"plant-tour-confirmation-{$serviceRequest->id}.pdf\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function lockPendingTour(ServiceRequest $serviceRequest): void
    {
        $serviceRequest->setRawAttributes(ServiceRequest::query()->lockForUpdate()->findOrFail($serviceRequest->id)->getAttributes(), true);
        abort_if($error = $this->tourRequestAccessError($serviceRequest, ServiceRequestStatus::Pending), 422, $error ?? '');
    }

    /** @param array<string, string|null> $values */
    private function notifyClient(ServiceRequest $serviceRequest, FormTemplateKey $key, string $message, array $values = []): string
    {
        $serviceRequest->load(['client', 'tourRequest']);
        $error = $this->formTemplateMailer->send($key, $serviceRequest->client->email, [
            'client_name' => $serviceRequest->client->fullname,
            'reference_no' => 'SR-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT),
            'visit_date' => $serviceRequest->tourRequest->visit_date?->format('F j, Y'),
            'visit_time' => $this->formatTime($serviceRequest->tourRequest->visit_time),
            ...$values,
        ], $serviceRequest);
        $this->serviceRequestLogger->log($serviceRequest, $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null ? "{$key->label()} email queued for the client." : "{$key->label()} email could not be queued: {$error}");

        return $error === null ? "{$message}. Email queued for delivery." : "{$message}, but the email could not be queued: {$error}";
    }

    public function editFeedback(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::PlantTourServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a plant tour request.');
        }

        // Stays reachable after the request is marked completed, so the admin can still review the submitted response.
        if (! in_array($serviceRequest->status, [ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed], true)) {
            return to_route('admin.requests.index')->with('error', 'This request is not awaiting feedback.');
        }

        if (! $serviceRequest->tourRequest()->exists()) {
            return to_route('admin.requests.index')->with('error', 'The plant tour request details are not available.');
        }

        $serviceRequest->load(['client', 'tourRequest.items', 'logs', 'feedbackLinks']);

        return Inertia::render('admin/tour-feedback-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'tourRequest' => $this->tourRequestPayload($serviceRequest->tourRequest),
            'logs' => $this->logsPayload($serviceRequest),
            'feedbackLinks' => $this->feedbackLinksPayload($serviceRequest),
        ]);
    }

    public function generateFeedbackLink(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->tourRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $link = $this->feedbackLinks->generate($serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::FeedbackLinkGenerated,
            "Feedback form link generated, valid until {$link->expires_at->format('F j, Y g:i A')}.",
        );

        return back()->with('success', "Feedback form link generated:\n".url("/feedback/{$link->token}"));
    }

    public function viewFeedbackResponse(ServiceRequest $serviceRequest, FeedbackLink $feedbackLink): Response|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::PlantTourServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a plant tour request.');
        }

        abort_unless($feedbackLink->service_request_id === $serviceRequest->id, 404);

        $feedbackLink->load('response');
        $response = $feedbackLink->response;

        if ($response === null) {
            return back()->with('error', 'No response has been submitted for this link yet.');
        }

        $serviceRequest->load('client');
        $snapshot = $response->snapshot ?? ['dimensions' => [], 'ratings' => [], 'questions' => []];

        return Inertia::render('admin/feedback-response', [
            'client' => [
                ...$this->clientPayload($serviceRequest->client),
                'service' => $serviceRequest->service->label(),
                'reference_no' => 'SR-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT),
            ],
            'submittedAt' => $response->created_at->format('F j, Y g:i A'),
            'dimensions' => $snapshot['dimensions'],
            'ratings' => $snapshot['ratings'],
            'questions' => $snapshot['questions'],
            'responseRatings' => $response->ratings,
            'responseAnswers' => $response->answers,
            'showEmoji' => FeedbackDisplaySetting::showEmoji(),
            'pdfUrl' => route('admin.requests.tour.feedback.response.pdf', [$serviceRequest, $feedbackLink]),
        ]);
    }

    public function downloadFeedbackResponsePdf(ServiceRequest $serviceRequest, FeedbackLink $feedbackLink): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::PlantTourServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a plant tour request.');
        }

        abort_unless($feedbackLink->service_request_id === $serviceRequest->id, 404);

        $feedbackLink->load('response');
        $response = $feedbackLink->response;

        if ($response === null) {
            return back()->with('error', 'No response has been submitted for this link yet.');
        }

        $serviceRequest->load('client');
        $snapshot = $response->snapshot ?? ['dimensions' => [], 'ratings' => [], 'questions' => []];
        $client = $this->clientPayload($serviceRequest->client);

        $document = app(FeedbackPdfService::class)->render(
            $snapshot['dimensions'],
            $snapshot['questions'],
            $snapshot['ratings'],
            [
                ...$client,
                'reference_no' => 'SR-'.str_pad((string) $serviceRequest->id, 6, '0', STR_PAD_LEFT),
                'date' => $response->created_at->format('F j, Y'),
                'age_bracket' => match (true) {
                    ($client['age'] ?? null) === null => null,
                    $client['age'] <= 20 => 'less than 20 yrs old',
                    $client['age'] <= 30 => '21-30 yrs old',
                    $client['age'] <= 50 => '31-50 yrs old',
                    $client['age'] <= 59 => '51-59 yrs old',
                    default => '60 yrs old and above',
                },
            ],
            $response->ratings,
            $response->answers,
            showEmoji: FeedbackDisplaySetting::showEmoji(),
        );

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"feedback-response-{$serviceRequest->id}.pdf\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function feedbackLinksPayload(ServiceRequest $serviceRequest): array
    {
        return $serviceRequest->feedbackLinks
            ->sortByDesc('id')
            ->map(fn (FeedbackLink $link): array => [
                'id' => $link->id,
                'url' => url("/feedback/{$link->token}"),
                'expires_at' => $link->expires_at->format('F j, Y g:i A'),
                'is_expired' => $link->isExpired(),
                'is_submitted' => $link->isSubmitted(),
                'response_url' => $link->isSubmitted()
                    ? "/admin/requests/{$serviceRequest->id}/tour-request/feedback/responses/{$link->id}"
                    : null,
            ])
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function logsPayload(ServiceRequest $serviceRequest): array
    {
        return $serviceRequest->logs->map(fn (ServiceRequestLog $log): array => [
            'id' => $log->id,
            'action' => $log->action->label(),
            'description' => $log->description,
            'actor_name' => $log->actor_name,
            'created_at' => $log->created_at?->format('F d, Y H:i:s'),
        ])->all();
    }

    /** @return array<string, mixed> */
    private function serviceRequestPayload(ServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'service' => $serviceRequest->service->label(),
            'status_value' => $serviceRequest->status->value,
            'description' => $serviceRequest->description,
            'created_at' => $serviceRequest->created_at->format('F, d Y H:i:s'),
            'client' => $this->clientPayload($serviceRequest->client),
        ];
    }

    /** @return array<string, mixed> */
    private function tourRequestPayload(TourRequest $tourRequest): array
    {
        $item = $tourRequest->items->first();

        return [
            'visit_date_value' => $tourRequest->visit_date?->toDateString(),
            'visit_time_value' => substr($tourRequest->visit_time ?? '', 0, 5),
            ...$tourRequest->only(['prepared_by', 'noted_by', 'conforme_primary', 'conforme_secondary', 'conforme_optional', 'remarks']),
            'visit_date' => $tourRequest->visit_date?->format('F j, Y'),
            'visit_time' => $this->formatTime($tourRequest->visit_time),
            'message' => $tourRequest->message,
            'no_persons' => $tourRequest->no_persons,
            'no_groups' => $tourRequest->no_groups,
            'technology_assistance' => $tourRequest->technology_assistance,
            'visit_objectives' => $tourRequest->visit_objectives,
            'testing_lab' => $this->facilityLabels(TestingLabFacility::class, $item?->testing_lab ?? []),
            'pilot_plant' => $this->facilityLabels(PilotPlantFacility::class, $item?->pilot_plant ?? []),
            'others' => $this->facilityLabels(OtherTourFacility::class, $item?->others ?? []),
        ];
    }

    /**
     * @param  class-string<TestingLabFacility|PilotPlantFacility|OtherTourFacility>  $enum
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function facilityLabels(string $enum, array $values): array
    {
        return array_map(fn (string $value): string => $enum::from($value)->label(), $values);
    }

    /** @return array<string, mixed> */
    private function clientPayload(Client $client): array
    {
        return [
            'fullname' => $client->fullname,
            'email' => $client->email,
            'mobile_no' => $client->mobile_no,
            'tel_no' => $client->tel_no,
            'fax_no' => $client->fax_no,
            'age' => $client->age,
            'gender' => $client->gender,
            'address' => trim("{$client->address}, {$client->municipality}, {$client->province}, {$client->region}", ', '),
            'type_client' => $client->type_client->label(),
            'company_or_school' => $client->company ?? $client->school_name,
        ];
    }

    /** @param  ServiceRequestStatus|array<int, ServiceRequestStatus>  $expectedStatus */
    private function tourRequestAccessError(ServiceRequest $serviceRequest, ServiceRequestStatus|array $expectedStatus): ?string
    {
        abort_unless(Auth::user()?->services()->where('service', ClientService::PlantTourServices)->exists(), 403);

        if ($serviceRequest->service !== ClientService::PlantTourServices) {
            return 'This request cannot be handled as a plant tour request.';
        }

        $allowedStatuses = is_array($expectedStatus) ? $expectedStatus : [$expectedStatus];

        if (! in_array($serviceRequest->status, $allowedStatuses, true)) {
            $label = implode(' or ', array_map(fn (ServiceRequestStatus $status): string => $status->label(), $allowedStatuses));

            return "This request is not {$label}.";
        }

        if ($serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved) {
            return 'Confirm the appointment before proceeding with the plant tour request.';
        }

        if (! $serviceRequest->tourRequest()->exists()) {
            return 'The plant tour request details are not available.';
        }

        return null;
    }

    private function formatTime(?string $time): string
    {
        if ($time === null || $time === '') {
            return '';
        }

        return Carbon::parse($time)->format('g:i A');
    }
}
