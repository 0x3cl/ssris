<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRddRequestRequest;
use App\Mail\FeedbackReminderMail;
use App\Models\Client;
use App\Models\RddRequest;
use App\Models\RddRequestItem;
use App\Models\ServiceRequest;
use App\Models\SmtpSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class RddRequestController extends Controller
{
    public function create(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load('client');

        return Inertia::render('admin/rdd-request-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
        ]);
    }

    public function store(StoreRddRequestRequest $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::Pending)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $serviceRequest): void {
            $subTotal = collect($data['items'])->sum(fn (array $item): float => $item['quantity'] * $item['unit_fee']);
            $discount = round($subTotal * ($data['discount_percentage'] / 100), 2);

            $rddRequest = RddRequest::create([
                'service_request_id' => $serviceRequest->id,
                'reference_no' => $this->nextReferenceNo($serviceRequest, $data['reference_prefix']),
                'due_date' => $data['due_date'],
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total_fee' => $subTotal - $discount,
            ]);

            foreach ($data['items'] as $item) {
                $rddRequest->items()->create([
                    'item' => $item['item'],
                    'specification' => $item['specification'],
                    'quantity' => $item['quantity'],
                    'unit_fee' => $item['unit_fee'],
                    'total_fee' => $item['quantity'] * $item['unit_fee'],
                ]);
            }

            $serviceRequest->update(['status' => ServiceRequestStatus::ForPayment]);
        });

        return to_route('admin.requests.rdd.payment.edit', $serviceRequest)
            ->with('success', 'The R&D request form has been saved and the request moved to for payment.');
    }

    public function downloadPdf(ServiceRequest $serviceRequest): HttpResponse|RedirectResponse
    {
        if ($serviceRequest->service !== ClientService::RddServices) {
            return to_route('admin.requests.index')->with('error', 'This request cannot be handled as an R&D request.');
        }

        $serviceRequest->load(['client', 'rddRequest.items']);
        $rddRequest = $serviceRequest->rddRequest;

        if ($rddRequest === null) {
            return to_route('admin.requests.index')->with('error', 'The R&D request form is not available.');
        }

        $logoPath = resource_path('images/ptri-logo.jpg');
        $logoDataUri = 'data:image/jpeg;base64,'.base64_encode(file_get_contents($logoPath));

        $pdf = Pdf::loadView('pdfs.rdd-request', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'rddRequest' => [
                ...$this->rddRequestPayload($rddRequest),
                'payment_verified_at' => $rddRequest->op_no ? $rddRequest->updated_at->format('Y-m-d') : null,
            ],
            'logoPath' => $logoDataUri,
            'peso' => fn (float $amount): string => 'PHP '.number_format($amount, 2),
        ])->setPaper('a4');

        return $pdf->download("{$rddRequest->reference_no}.pdf");
    }

    public function editPayment(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireRddRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'rddRequest.items']);

        return Inertia::render('admin/rdd-payment-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'rddRequest' => $this->rddRequestPayload($serviceRequest->rddRequest),
        ]);
    }

    public function updatePayment(Request $request, ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::ForPayment, requireRddRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $data = $request->validate([
            'op_no' => ['required', 'string', 'max:255'],
            'or_no' => ['required', 'string', 'max:255'],
            'confirmation_code' => ['required', 'digits:4'],
        ]);

        $this->validateConfirmationCode($request, $data['confirmation_code']);

        DB::transaction(function () use ($serviceRequest, $data): void {
            $serviceRequest->rddRequest->update([
                'op_no' => $data['op_no'],
                'or_no' => $data['or_no'],
            ]);

            $serviceRequest->update(['status' => ServiceRequestStatus::AwaitingFeedback]);
        });

        return to_route('admin.requests.rdd.feedback.edit', $serviceRequest)
            ->with('success', 'Payment verified. The request is now awaiting feedback.');
    }

    public function editFeedback(ServiceRequest $serviceRequest): Response|RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireRddRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $serviceRequest->load(['client', 'rddRequest.items']);

        return Inertia::render('admin/rdd-feedback-form', [
            'serviceRequest' => $this->serviceRequestPayload($serviceRequest),
            'rddRequest' => $this->rddRequestPayload($serviceRequest->rddRequest),
        ]);
    }

    public function sendFeedbackReminder(ServiceRequest $serviceRequest): RedirectResponse
    {
        if ($error = $this->rddRequestAccessError($serviceRequest, ServiceRequestStatus::AwaitingFeedback, requireRddRequest: true)) {
            return to_route('admin.requests.index')->with('error', $error);
        }

        $smtpSetting = SmtpSetting::query()->first();

        if ($smtpSetting === null) {
            return back()->with('error', 'Configure SMTP settings before sending reminder emails.');
        }

        try {
            $this->configureMailer($smtpSetting);

            $serviceRequest->load('client');
            Mail::mailer('smtp')->to($serviceRequest->client->email)->send(new FeedbackReminderMail($serviceRequest));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'We could not send the reminder email. Please check the SMTP configuration and try again.');
        }

        return back()->with('success', 'Feedback reminder email sent to the client.');
    }

    /** @return array<string, mixed> */
    private function serviceRequestPayload(ServiceRequest $serviceRequest): array
    {
        return [
            'id' => $serviceRequest->id,
            'service' => $serviceRequest->service->label(),
            'description' => $serviceRequest->description,
            'created_at' => $serviceRequest->created_at->format('F, d Y H:i:s'),
            'client' => $this->clientPayload($serviceRequest->client),
        ];
    }

    /** @return array<string, mixed> */
    private function rddRequestPayload(RddRequest $rddRequest): array
    {
        return [
            'reference_no' => $rddRequest->reference_no,
            'due_date' => $rddRequest->due_date->toDateString(),
            'sub_total' => $rddRequest->sub_total,
            'discount' => $rddRequest->discount,
            'total_fee' => $rddRequest->total_fee,
            'op_no' => $rddRequest->op_no,
            'or_no' => $rddRequest->or_no,
            'items' => $rddRequest->items->map(fn (RddRequestItem $item): array => [
                'item' => $item->item,
                'specification' => $item->specification,
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

    private function configureMailer(SmtpSetting $smtpSetting): void
    {
        config([
            'mail.mailers.smtp.host' => $smtpSetting->host,
            'mail.mailers.smtp.port' => $smtpSetting->port,
            'mail.mailers.smtp.username' => $smtpSetting->username,
            'mail.mailers.smtp.password' => $smtpSetting->password,
        ]);
    }

    private function rddRequestAccessError(ServiceRequest $serviceRequest, ServiceRequestStatus $expectedStatus, bool $requireRddRequest = false): ?string
    {
        if ($serviceRequest->service !== ClientService::RddServices) {
            return 'This request cannot be handled as an R&D request.';
        }

        if ($serviceRequest->status !== $expectedStatus) {
            return "This request is not {$expectedStatus->label()}.";
        }

        if (! $requireRddRequest && $serviceRequest->is_appointment && ! $serviceRequest->is_appointment_approved) {
            return 'Confirm the appointment before creating the R&D request form.';
        }

        $hasRddRequest = $serviceRequest->rddRequest()->exists();

        if ($requireRddRequest && ! $hasRddRequest) {
            return 'The R&D request form is not available.';
        }

        if (! $requireRddRequest && $hasRddRequest) {
            return 'This request already has an R&D request form.';
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

    private function nextReferenceNo(ServiceRequest $serviceRequest, string $prefix): string
    {
        $client = $serviceRequest->client;
        $initials = mb_strtoupper(mb_substr($client->firstname, 0, 1).mb_substr($client->lastname, 0, 1));
        $base = "{$prefix}-{$initials}";

        $sequence = RddRequest::query()->where('reference_no', 'like', "{$base}-%")->lockForUpdate()->count();

        return sprintf('%s-%05d', $base, $sequence + 1);
    }
}
