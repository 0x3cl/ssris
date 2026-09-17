<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestLogAction;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabRequestRequest;
use App\Models\Client;
use App\Models\FeedbackDisplaySetting;
use App\Models\FeedbackLink;
use App\Models\LabRequest;
use App\Models\LabRequestItem;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestLog;
use App\Services\FeedbackLinkService;
use App\Services\FeedbackPdfService;
use App\Services\FormTemplateMailer;
use App\Services\LabPdfService;
use App\Services\ServiceRequestLogger;
use App\Services\UlimsClient;
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

class LabRequestController extends Controller
{
    public function __construct(
        private readonly ServiceRequestLogger $serviceRequestLogger,
        private readonly FormTemplateMailer $formTemplateMailer,
        private readonly FeedbackLinkService $feedbackLinks,
        private readonly UlimsClient $ulims,
    ) {}

    public function create(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load('client');

        $testCategories = $this->ulims->testCategories();
        $sampleTypes = $this->ulims->sampleTypes();
        $testMethods = $this->ulims->testMethods();

        return Inertia::render('admin/lab-request-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'testCategories' => $testCategories,
            'sampleTypes' => $sampleTypes,
            'testMethods' => $testMethods,
        ]);
    }

    public function store(StoreLabRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest): void {
            $subTotal = collect($data['items'])->sum(fn (array $item): float => $item['quantity'] * $item['unit_fee']);
            $discount = round($subTotal * ($data['discount_percentage'] / 100), 2);

            $labRequest = LabRequest::create([
                'service_request_id' => $serviceRequest->id,
                'quotation_no' => $this->nextQuotationNo(),
                'due_date' => $data['due_date'],
                'test_category' => $data['test_category'],
                'ulims_test_category_id' => $data['ulims_test_category_id'] ?? null,
                'sample_type' => $data['sample_type'],
                'ulims_sample_type_id' => $data['ulims_sample_type_id'] ?? null,
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total_fee' => $subTotal - $discount,
            ]);

            foreach ($data['items'] as $item) {
                $labRequest->items()->create([
                    'test' => $item['test'],
                    'ulims_test_id' => $item['ulims_test_id'] ?? null,
                    'method' => $item['method'],
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
            'Lab services request form saved and the request moved to for payment.',
        );

        return to_route('admin.requests.lab.payment.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'payment-verification'])
            ->with('success', 'The lab services request form has been saved and the request moved to for payment.');
    }

    public function downloadPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::LabServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a lab services request.');
        }

        $serviceRequest->load(['client', 'labRequest.items']);
        $labRequest = $serviceRequest->labRequest;

        if ($labRequest === null) {
            return to_route('admin.requests.index')->with('error', 'The lab services request form is not available.');
        }

        $document = app(LabPdfService::class)->render(
            [...$this->serviceRequestPayload($serviceRequest), 'created_at' => $serviceRequest->created_at->format('Y-m-d H:i:s')],
            [
                ...$this->labRequestPayload($labRequest),
                'payment_verified_at' => $labRequest->op_no ? $labRequest->updated_at->format('Y-m-d') : null,
            ],
        );

        $filename = "{$labRequest->quotation_no}.pdf";

        return response($document, 200, [
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function editPayment(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireLabRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'labRequest.items']);

        return Inertia::render('admin/lab-payment-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'labRequest' => $this->labRequestPayload($serviceRequest->labRequest),
        ]);
    }

    public function updatePayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireLabRequest: true)) {
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
                $serviceRequest->labRequest->update([
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

        return to_route('admin.requests.lab.feedback.edit', ['serviceRequest' => $serviceRequest, 'tab' => 'feedback'])
            ->with('success', 'Payment verified. The request is now awaiting feedback.');
    }

    public function downloadPaymentAttachment(ServiceRequest $serviceRequest, string $type): BinaryFileResponse|StreamedResponse
    {
        abort_unless(in_array($type, ['op', 'or'], true), 404);
        $path = $serviceRequest->labRequest?->getAttribute($type.'_attachment');
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function sendPaymentReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireLabRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'labRequest']);

        $error = $this->formTemplateMailer->send(FormTemplateKey::PaymentReminder, $serviceRequest->client->email, [
            'name' => $serviceRequest->client->fullname,
            'reference_no' => $serviceRequest->labRequest->quotation_no,
            'amount' => 'PHP '.number_format((float) $serviceRequest->labRequest->total_fee, 2),
            'due' => $serviceRequest->labRequest->due_date->format('F j, Y'),
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
        if ($serviceRequest->service !== ClientService::LabServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a lab services request.');
        }

        // Stays reachable after the request is marked completed, so the admin can still review the submitted response.
        if (! in_array($serviceRequest->status, [ServiceRequestStatus::AwaitingFeedback, ServiceRequestStatus::Completed], true)) {
            return to_route('admin.requests.index')->with('error', 'This request is not awaiting feedback.');
        }

        if (! $serviceRequest->labRequest()->exists()) {
            return to_route('admin.requests.index')->with('error', 'The lab services request form is not available.');
        }

        $serviceRequest->load(['client', 'labRequest.items', 'logs', 'feedbackLinks']);

        return Inertia::render('admin/lab-feedback-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'labRequest' => $this->labRequestPayload($serviceRequest->labRequest),
            'logs' => $this->logsPayload($serviceRequest),
            'feedbackLinks' => $this->feedbackLinksPayload($serviceRequest),
        ]);
    }

    public function sendFeedbackReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireLabRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'labRequest']);

        $link = $this->feedbackLinks->generate($serviceRequest);

        $error = $this->formTemplateMailer->send(FormTemplateKey::FeedbackReminder, $serviceRequest->client->email, [
            'reference' => $serviceRequest->labRequest->quotation_no,
            'due_date' => $serviceRequest->labRequest->due_date->format('F j, Y'),
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
        if ($error = $this->labRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireLabRequest: true)) {
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
        if ($serviceRequest->service !== ClientService::LabServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a lab services request.');
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
            'pdfUrl' => route('admin.requests.lab.feedback.response.pdf', [$serviceRequest, $feedbackLink]),
        ]);
    }

    public function downloadFeedbackResponsePdf(ServiceRequest $serviceRequest, FeedbackLink $feedbackLink): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::LabServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as a lab services request.');
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
                    ? "/admin/requests/{$serviceRequest->id}/lab-request/feedback/responses/{$link->id}"
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
    private function labRequestPayload(LabRequest $labRequest): array
    {
        return [
            'quotation_no' => $labRequest->quotation_no,
            'due_date' => $labRequest->due_date->toDateString(),
            'test_category' => $labRequest->test_category,
            'ulims_test_category_id' => $labRequest->ulims_test_category_id,
            'sample_type' => $labRequest->sample_type,
            'ulims_sample_type_id' => $labRequest->ulims_sample_type_id,
            'sub_total' => $labRequest->sub_total,
            'discount' => $labRequest->discount,
            'total_fee' => $labRequest->total_fee,
            'op_no' => $labRequest->op_no,
            'or_no' => $labRequest->or_no,
            'op_attachment_url' => $labRequest->op_attachment ? route('admin.requests.lab.attachment', ['serviceRequest' => $labRequest->service_request_id, 'type' => 'op']) : null,
            'or_attachment_url' => $labRequest->or_attachment ? route('admin.requests.lab.attachment', ['serviceRequest' => $labRequest->service_request_id, 'type' => 'or']) : null,
            'items' => $labRequest->items->map(fn (LabRequestItem $item): array => [
                'test' => $item->test,
                'ulims_test_id' => $item->ulims_test_id,
                'method' => $item->method,
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

    private function labRequestAccessError(ServiceRequest $serviceRequest, ServiceRequestStatus $expectedStatus, bool $requireLabRequest = false): ?string
    {
        if ($serviceRequest->service !== ClientService::LabServices) {
            return 'This request cannot be handled as a lab services request.';
        }

        if ($serviceRequest->status !== $expectedStatus) {
            return "This request is not {$expectedStatus->label()}.";
        }

        if (! $requireLabRequest && $serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved) {
            return 'Confirm the appointment before creating the lab services request form.';
        }

        $hasLabRequest = $serviceRequest->labRequest()->exists();

        if ($requireLabRequest && ! $hasLabRequest) {
            return 'The lab services request form is not available.';
        }

        if (! $requireLabRequest && $hasLabRequest) {
            return 'This request already has a lab services request form.';
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

    private function nextQuotationNo(): string
    {
        $prefix = now()->format('y-m');

        $sequence = LabRequest::query()->where('quotation_no', 'like', "{$prefix}-%")->lockForUpdate()->count();

        return sprintf('%s-%03d-LAB', $prefix, $sequence + 1);
    }
}
