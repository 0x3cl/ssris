<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProcessingRequestRequest;
use App\Models\Client;
use App\Models\FeedbackLink;
use App\Models\ProcessingRequest;
use App\Models\ProcessingRequestItem;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use App\Services\FeedbackLinkService;
use App\Services\FormTemplateMailer;
use App\Services\ProcessingPdfService;
use App\Services\ServiceRequestLogger;
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

class ProcessingRequestController extends Controller
{
    public function __construct(
        private readonly ServiceRequestLogger $serviceRequestLogger,
        private readonly FormTemplateMailer $formTemplateMailer,
        private readonly FeedbackLinkService $feedbackLinks,
    ) {}

    public function create(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load('client');

        return Inertia::render('admin/processing-request-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
        ]);
    }

    public function store(StoreProcessingRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest): void {
            $subTotal = collect($data['items'])->sum(fn (array $item): float => $item['quantity'] * $item['unit_fee']);
            $discount = round($subTotal * ($data['discount_percentage'] / 100), 2);

            $processingRequest = ProcessingRequest::create([
                'service_request_id' => $serviceRequest->id,
                'reference_no' => $this->nextReferenceNo($serviceRequest),
                'sample_no' => $this->nextSampleNo($serviceRequest),
                'sample_type' => $data['sample_type'],
                'due_date' => $data['due_date'],
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total_fee' => $subTotal - $discount,
            ]);

            foreach ($data['items'] as $item) {
                $processingRequest->items()->create([
                    'item' => $item['item'],
                    'weight' => $item['weight'],
                    'quantity' => $item['quantity'],
                    'unit_fee' => $item['unit_fee'],
                    'total_fee' => $item['quantity'] * $item['unit_fee'],
                ]);
            }

            $serviceRequest->update(['status' => ServiceRequestStatus::ForPayment]);
        });

        $this->serviceRequestLogger->log(
            $serviceRequest,
            ServiceRequestLogAction::StatusChanged,
            'Processing services request form saved and the request moved to for payment.',
        );

        return to_route('admin.requests.processing.payment.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'payment-verification'])
            ->with('success', 'The processing services request form has been saved and the request moved to for payment.');
    }

    public function downloadPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::ProcessingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a processing services request.');
        }

        $serviceRequest->load(['client', 'processingRequest.items']);
        $processingRequest = $serviceRequest->processingRequest;

        if ($processingRequest === null) {
            return to_route('admin.requests.index')->with('error', 'The processing services request form is not available.');
        }

        $document = app(ProcessingPdfService::class)->render(
            [...$this->serviceRequestPayload($serviceRequest), 'created_at' => $serviceRequest->created_at->format('Y-m-d H:i:s')],
            [
                ...$this->processingRequestPayload($processingRequest),
                'payment_verified_at' => $processingRequest->op_no ? $processingRequest->updated_at->format('Y-m-d') : null,
            ],
        );

        $filename = "{$processingRequest->reference_no}.pdf";

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function editPayment(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireProcessingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'processingRequest.items']);

        return Inertia::render('admin/processing-payment-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'processingRequest' => $this->processingRequestPayload($serviceRequest->processingRequest),
        ]);
    }

    public function updatePayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireProcessingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validate([
            'op_no' => ['required', 'string', 'max:255'],
            'or_no' => ['required', 'string', 'max:255'],
            'op_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'or_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'confirmation_code' => ['required', 'digits:4'],
        ]);

        $this->validateConfirmationCode($request, $data['confirmation_code']);

        $attachments = [];
        try {
            foreach (['op_attachment', 'or_attachment'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store('payment-attachments', 'local');
                    if ($path === false) {
                        throw new \RuntimeException('The payment attachment could not be stored.');
                    }
                    $attachments[$field] = $path;
                }
            }
            DB::transaction(function () use ($serviceRequest, $data, $attachments): void {
                $serviceRequest->processingRequest->update([
                    ...$attachments,
                    'op_no' => $data['op_no'],
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

        return to_route('admin.requests.processing.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback'])
            ->with('success', 'Payment verified. The request is now awaiting feedback.');
    }

    public function downloadPaymentAttachment(ServiceRequest $serviceRequest, string $type): BinaryFileResponse|StreamedResponse
    {
        abort_unless(in_array($type, ['op', 'or'], true), 404);
        $path = $serviceRequest->processingRequest?->getAttribute($type.'_attachment');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function sendPaymentReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireProcessingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'processingRequest']);

        $error = $this->formTemplateMailer->send(FormTemplateKey::PaymentReminder, $serviceRequest->client->email, [
            'name' => $serviceRequest->client->fullname,
            'reference_no' => $serviceRequest->processingRequest->reference_no,
            'amount' => 'PHP '.number_format((float) $serviceRequest->processingRequest->total_fee, 2),
            'due' => $serviceRequest->processingRequest->due_date->format('F j, Y'),
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
        if ($serviceRequest->service !== ClientService::ProcessingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a processing services request.');
        }

        // Stays reachable after the request is marked completed, so the admin can still review the submitted response.
        if (! in_array($serviceRequest->status, [ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed], true)) {
            return to_route('admin.requests.index')->with('error', 'This request is not awaiting feedback.');
        }

        if (! $serviceRequest->processingRequest()->exists()) {
            return to_route('admin.requests.index')->with('error', 'The processing services request form is not available.');
        }

        $serviceRequest->load(['client', 'processingRequest.items', 'logs', 'feedbackLinks']);

        return Inertia::render('admin/processing-feedback-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'processingRequest' => $this->processingRequestPayload($serviceRequest->processingRequest),
            'logs' => $this->logsPayload($serviceRequest),
            'feedbackLinks' => $this->feedbackLinksPayload($serviceRequest),
        ]);
    }

    public function sendFeedbackReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireProcessingRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'processingRequest']);

        $link = $this->feedbackLinks->generate($serviceRequest);

        $error = $this->formTemplateMailer->send(FormTemplateKey::FeedbackReminder, $serviceRequest->client->email, [
            'reference' => $serviceRequest->processingRequest->reference_no,
            'due_date' => $serviceRequest->processingRequest->due_date->format('F j, Y'),
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
        if ($error = $this->processingRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireProcessingRequest: true)) {
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
        if ($serviceRequest->service !== ClientService::ProcessingServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a processing services request.');
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
                    ? "/admin/requests/{$serviceRequest->id}/processing-request/feedback/responses/{$link->id}"
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
    private function processingRequestPayload(ProcessingRequest $processingRequest): array
    {
        return [
            'reference_no' => $processingRequest->reference_no,
            'sample_no' => $processingRequest->sample_no,
            'sample_type' => $processingRequest->sample_type,
            'due_date' => $processingRequest->due_date->toDateString(),
            'sub_total' => $processingRequest->sub_total,
            'discount' => $processingRequest->discount,
            'total_fee' => $processingRequest->total_fee,
            'op_no' => $processingRequest->op_no,
            'or_no' => $processingRequest->or_no,
            'op_attachment_url' => $processingRequest->op_attachment ? route('admin.requests.processing.attachment', ['serviceRequest' => $processingRequest->service_request_id, 'type' => 'op']) : null,
            'or_attachment_url' => $processingRequest->or_attachment ? route('admin.requests.processing.attachment', ['serviceRequest' => $processingRequest->service_request_id, 'type' => 'or']) : null,
            'items' => $processingRequest->items->map(fn (ProcessingRequestItem $item): array => [
                'item' => $item->item,
                'weight' => $item->weight,
                'quantity' => $item->quantity,
                'unit_fee' => $item->unit_fee,
                'total_fee' => $item->total_fee,
            ]),
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

    private function processingRequestAccessError(ServiceRequest $serviceRequest, ServiceRequestStatus $expectedStatus, bool $requireProcessingRequest = false): ?string
    {
        if ($serviceRequest->service !== ClientService::ProcessingServices) {
            return 'This request cannot be handled as a processing services request.';
        }

        if ($serviceRequest->status !== $expectedStatus) {
            return "This request is not {$expectedStatus->label()}.";
        }

        if (! $requireProcessingRequest && $serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved) {
            return 'Confirm the appointment before creating the processing services request form.';
        }

        $hasProcessingRequest = $serviceRequest->processingRequest()->exists();

        if ($requireProcessingRequest && ! $hasProcessingRequest) {
            return 'The processing services request form is not available.';
        }

        if (! $requireProcessingRequest && $hasProcessingRequest) {
            return 'This request already has a processing services request form.';
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
        $base = "PRC-{$initials}";

        $sequence = ProcessingRequest::query()->where('reference_no', 'like', "{$base}-%")->lockForUpdate()->count();

        return sprintf('%s-%05d', $base, $sequence + 1);
    }

    private function nextSampleNo(ServiceRequest $serviceRequest): string
    {
        $client = $serviceRequest->client;
        $initials = mb_strtoupper(mb_substr($client->firstname, 0, 1).mb_substr($client->lastname, 0, 1));

        $sequence = ProcessingRequest::query()->where('sample_no', 'like', "%-{$initials}")->lockForUpdate()->count();

        return sprintf('%05d-%s', $sequence + 1, $initials);
    }
}
