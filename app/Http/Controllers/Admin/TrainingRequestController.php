<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrainingRequestFeeRequest;
use App\Http\Requests\StoreTrainingRequestRequest;
use App\Models\Client;
use App\Models\FeedbackLink;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestFee;
use App\Services\FeedbackLinkService;
use App\Services\FormTemplateMailer;
use App\Services\ServiceRequestLogger;
use App\Services\TrainingServiceFeePdfService;
use App\Services\TrainingServiceRequestPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrainingRequestController extends Controller
{
    public function __construct(
        private readonly ServiceRequestLogger $serviceRequestLogger,
        private readonly FormTemplateMailer $formTemplateMailer,
        private readonly FeedbackLinkService $feedbackLinks,
    ) {}

    public function create(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load('client');

        return Inertia::render('admin/training-request-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
        ]);
    }

    public function store(StoreTrainingRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest): void {
            TrainingRequest::create(['service_request_id' => $serviceRequest->id, ...$data]);

            $serviceRequest->update(['status' => ServiceRequestStatus::ForServiceFee]);
        });

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::StatusChanged,
            'Training request form saved and the request moved to for service fee.',
        );

        return to_route('admin.requests.training.fee.create', $serviceRequest)
            ->with('success', 'The training request form has been saved and the request moved to for service fee.');
    }

    public function downloadPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::TrainingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a training request.');
        }

        $serviceRequest->load(['client', 'trainingRequest.fee']);
        $trainingRequest = $serviceRequest->trainingRequest;

        if ($trainingRequest === null) {
            return to_route('admin.requests.index')->with('error', 'The training request form is not available.');
        }

        $document = app(TrainingServiceRequestPdfService::class)->render(
            [
                'created_at' => $serviceRequest->created_at->format('Y-m-d H:i:s'),
                'client' => $this->pdfClientPayload($serviceRequest->client),
            ],
            [
                ...$this->trainingRequestPayload($trainingRequest),
                'reference_no' => $trainingRequest->fee?->reference_no,
            ],
        );

        $filename = ($trainingRequest->fee?->reference_no ?? "training-request-{$serviceRequest->id}").'.pdf';

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function createFee(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::ForServiceFee, requireTrainingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load('client');

        return Inertia::render('admin/training-fee-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'trainingRequest' => $this->trainingRequestPayload($serviceRequest->trainingRequest),
        ]);
    }

    public function storeFee(StoreTrainingRequestFeeRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::ForServiceFee, requireTrainingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest): void {
            $serviceRequest->trainingRequest->fee()->create([
                'reference_no' => $this->nextReferenceNo($serviceRequest),
                ...$data,
            ]);

            $serviceRequest->update(['status' => ServiceRequestStatus::ForPayment]);
        });

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::StatusChanged,
            'Service fee saved and the request moved to for payment.',
        );

        return to_route('admin.requests.training.payment.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'payment-verification'])
            ->with('success', 'The service fee has been saved and the request moved to for payment.');
    }

    public function downloadFeePdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::TrainingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a training request.');
        }

        $serviceRequest->load(['client', 'trainingRequest.fee']);
        $fee = $serviceRequest->trainingRequest?->fee;

        if ($fee === null) {
            return to_route('admin.requests.index')->with('error', 'The service fee is not available.');
        }

        $document = app(TrainingServiceFeePdfService::class)->render(
            [
                'reference_no' => $fee->reference_no,
                'date_time' => $fee->date_time->format('Y-m-d H:i:s'),
                'particulars' => $fee->particulars,
                'duration' => $fee->duration,
                'no_participants' => $fee->no_participants,
                'net_amount_due' => $fee->net_amount_due,
                'bill_no' => $fee->bill_no,
                'or_no' => $fee->or_no,
            ],
            $this->pdfClientPayload($serviceRequest->client),
        );

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"{$fee->reference_no}-fee.pdf\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function editPayment(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireTrainingRequest: true, requireFee: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'trainingRequest.fee']);

        return Inertia::render('admin/training-payment-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'trainingRequest' => $this->trainingRequestPayload($serviceRequest->trainingRequest),
        ]);
    }

    public function updatePayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireTrainingRequest: true, requireFee: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validate([
            'bill_no' => ['required', 'string', 'max:255'],
            'or_no' => ['required', 'string', 'max:255'],
            'bill_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'or_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'confirmation_code' => ['required', 'digits:4'],
        ]);

        $this->validateConfirmationCode($request, $data['confirmation_code']);

        $attachments = [];
        try {
            foreach (['bill_attachment', 'or_attachment'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store('payment-attachments', 'local');
                    if ($path === false) {
                        throw new \RuntimeException('The payment attachment could not be stored.');
                    }
                    $attachments[$field] = $path;
                }
            }
            DB::transaction(function () use ($serviceRequest, $data, $attachments): void {
                $serviceRequest->trainingRequest->fee->update([
                    ...$attachments,
                    'bill_no' => $data['bill_no'],
                    'or_no' => $data['or_no'],
                ]);

                $serviceRequest->update(['status' => ServiceRequestStatus::AwaitingFeedback]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($attachments));
            throw $exception;
        }

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::PaymentVerified,
            "Payment verified (OR No. {$data['or_no']}). The request is now awaiting feedback.",
        );

        return to_route('admin.requests.training.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback'])
            ->with('success', 'Payment verified. The request is now awaiting feedback.');
    }

    public function downloadPaymentAttachment(ServiceRequest $serviceRequest, string $type): BinaryFileResponse|StreamedResponse
    {
        abort_unless(in_array($type, ['bill', 'or'], true), 404);
        $path = $serviceRequest->trainingRequest?->fee?->getAttribute($type.'_attachment');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function sendPaymentReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireTrainingRequest: true, requireFee: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'trainingRequest.fee']);

        $error = $this->formTemplateMailer->send(FormTemplateKey::PaymentReminder, $serviceRequest->client->email, [
            'name' => $serviceRequest->client->fullname,
            'reference_no' => $serviceRequest->trainingRequest->fee->reference_no,
            'amount' => 'PHP '.number_format((float) $serviceRequest->trainingRequest->fee->net_amount_due, 2),
            'due' => $serviceRequest->trainingRequest->proposed_training_date->format('F j, Y'),
        ], $serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null
                ? 'Payment reminder email queued for delivery to the client.'
                : "Payment reminder email could not be queued: {$error}",
        );

        if ($error !== null) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Payment reminder email has been queued for delivery.');
    }

    public function editFeedback(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::TrainingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a training request.');
        }

        // Stays reachable after the request is marked completed, so the admin can still review the submitted response.
        if (! in_array($serviceRequest->status, [ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed], true)) {
            return to_route('admin.requests.index')->with('error', 'This request is not awaiting feedback.');
        }

        if (! $serviceRequest->trainingRequest()->exists()) {
            return to_route('admin.requests.index')->with('error', 'The training request form is not available.');
        }

        $serviceRequest->load(['client', 'trainingRequest.fee', 'logs', 'feedbackLinks']);

        return Inertia::render('admin/training-feedback-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'trainingRequest' => $this->trainingRequestPayload($serviceRequest->trainingRequest),
            'logs' => $this->logsPayload($serviceRequest),
            'feedbackLinks' => $this->feedbackLinksPayload($serviceRequest),
        ]);
    }

    public function sendFeedbackReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireTrainingRequest: true, requireFee: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'trainingRequest.fee']);

        $link = $this->feedbackLinks->generate($serviceRequest);

        $error = $this->formTemplateMailer->send(FormTemplateKey::FeedbackReminder, $serviceRequest->client->email, [
            'reference' => $serviceRequest->trainingRequest->fee->reference_no,
            'due_date' => $serviceRequest->trainingRequest->proposed_training_date->format('F j, Y'),
            'link' => url("/feedback/{$link->token}"),
        ], $serviceRequest);

        $this->serviceRequestLogger->log(
            $serviceRequest,
            $error === null ? ServiceRequestLogAction::EmailQueued : ServiceRequestLogAction::EmailFailed,
            $error === null
                ? "Feedback reminder email queued for delivery to the client, with a form link valid until {$link->expires_at->format('F j, Y g:i A')}."
                : "Feedback reminder email could not be queued: {$error}",
        );

        if ($error !== null) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Feedback reminder email has been queued for delivery.');
    }

    public function generateFeedbackLink(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->trainingRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireTrainingRequest: true, requireFee: true)) {
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
        if ($serviceRequest->service !== ClientService::TrainingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a training request.');
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
            'client' => [...$this->clientPayload($serviceRequest->client), 'service' => $serviceRequest->service->label()],
            'submittedAt' => $response->created_at->format('F j, Y g:i A'),
            'dimensions' => $snapshot['dimensions'],
            'ratings' => $snapshot['ratings'],
            'questions' => $snapshot['questions'],
            'responseRatings' => $response->ratings,
            'responseAnswers' => $response->answers,
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
                    ? "/admin/requests/{$serviceRequest->id}/training-request/feedback/responses/{$link->id}"
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
    private function trainingRequestPayload(TrainingRequest $trainingRequest): array
    {
        return [
            'training_course_requested' => $trainingRequest->training_course_requested,
            'estimated_participants' => $trainingRequest->estimated_participants,
            'proposed_training_date' => $trainingRequest->proposed_training_date->toDateString(),
            'proposed_training_venue' => $trainingRequest->proposed_training_venue,
            'beneficiary_name' => $trainingRequest->beneficiary_name,
            'purpose_of_training' => $trainingRequest->purpose_of_training,
            'assigned_trainer' => $trainingRequest->assigned_trainer,
            'assigned_assistant_trainer' => $trainingRequest->assigned_assistant_trainer,
            'official_course_title' => $trainingRequest->official_course_title,
            'approved_training_duration' => $trainingRequest->approved_training_duration,
            'training_type' => $trainingRequest->training_type,
            'special_type_details' => $trainingRequest->special_type_details,
            'fee' => $trainingRequest->fee ? $this->trainingRequestFeePayload($trainingRequest->fee, $trainingRequest->service_request_id) : null,
        ];
    }

    /** @return array<string, mixed> */
    private function trainingRequestFeePayload(TrainingRequestFee $fee, int $serviceRequestId): array
    {
        return [
            'reference_no' => $fee->reference_no,
            'date_time' => $fee->date_time->format('Y-m-d H:i:s'),
            'particulars' => $fee->particulars,
            'duration' => $fee->duration,
            'no_participants' => $fee->no_participants,
            'net_amount_due' => $fee->net_amount_due,
            'bill_no' => $fee->bill_no,
            'or_no' => $fee->or_no,
            'bill_attachment_url' => $fee->bill_attachment ? route('admin.requests.training.attachment', ['serviceRequest' => $serviceRequestId, 'type' => 'bill']) : null,
            'or_attachment_url' => $fee->or_attachment ? route('admin.requests.training.attachment', ['serviceRequest' => $serviceRequestId, 'type' => 'or']) : null,
        ];
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

    /** @return array<string, mixed> */
    private function pdfClientPayload(Client $client): array
    {
        return [
            ...$this->clientPayload($client),
            'address' => trim("{$client->address}, {$client->municipality}, {$client->province}", ', '),
            'region' => $client->region,
            'type_client_value' => $client->type_client->value,
            'gender_value' => $client->gender,
            'occupation' => $client->business_role?->label(),
            'business_line' => $client->products,
        ];
    }

    private function trainingRequestAccessError(
        ServiceRequest $serviceRequest,
        ServiceRequestStatus $expectedStatus,
        bool $requireTrainingRequest = false,
        bool $requireFee = false,
    ): ?string {
        if ($serviceRequest->service !== ClientService::TrainingServices) {
            return 'This request cannot be handled as a training request.';
        }

        if ($serviceRequest->status !== $expectedStatus) {
            return "This request is not {$expectedStatus->label()}.";
        }

        if (! $requireTrainingRequest && $serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved) {
            return 'Confirm the appointment before creating the training request form.';
        }

        $hasTrainingRequest = $serviceRequest->trainingRequest()->exists();

        if ($requireTrainingRequest && ! $hasTrainingRequest) {
            return 'The training request form is not available.';
        }

        if (! $requireTrainingRequest && $hasTrainingRequest) {
            return 'This request already has a training request form.';
        }

        if ($requireTrainingRequest) {
            $hasFee = $serviceRequest->trainingRequest->fee()->exists();

            if ($requireFee && ! $hasFee) {
                return 'The service fee is not available.';
            }

            if (! $requireFee && $hasFee) {
                return 'This request already has a service fee.';
            }
        }

        return null;
    }

    private function validateConfirmationCode(Request $request, string $code): void
    {
        $challenge = $request->session()->get('admin.delete_challenge');

        if (! is_string($challenge) || ! hash_equals($challenge, $code)) {
            throw ValidationException::withMessages(['confirmation_code' => 'Enter the displayed four-digit confirmation code to verify payment.']);
        }

        $request->session()->forget('admin.delete_challenge');
    }

    private function nextReferenceNo(ServiceRequest $serviceRequest): string
    {
        $client = $serviceRequest->client;
        $initials = mb_strtoupper(mb_substr($client->firstname, 0, 1).mb_substr($client->lastname, 0, 1));
        $base = "TRN-{$initials}";

        $sequence = TrainingRequestFee::query()->where('reference_no', 'like', "{$base}-%")->lockForUpdate()->count();

        return sprintf('%s-%05d', $base, $sequence + 1);
    }
}
