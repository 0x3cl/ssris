<?php

namespace App\Jobs;

use App\Enums\ServiceRequestLogAction;
use App\Models\ServiceRequest;
use App\Models\SmtpSetting;
use App\Services\ServiceRequestLogger;
use App\Services\SmtpMailerConfigurator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendQueuedServiceMail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public Mailable $mailable,
        public string $recipientEmail,
        public ?ServiceRequest $serviceRequest,
        public string $successDescription,
        public string $failureDescriptionPrefix,
    ) {}

    public function handle(SmtpMailerConfigurator $configurator, ServiceRequestLogger $logger): void
    {
        $smtpSetting = SmtpSetting::query()->firstOrFail();

        $configurator->configure($smtpSetting);

        Mail::mailer('smtp')->to($this->recipientEmail)->send($this->mailable);

        if ($this->serviceRequest !== null) {
            $logger->log($this->serviceRequest, ServiceRequestLogAction::EmailSent, $this->successDescription);
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->serviceRequest !== null) {
            app(ServiceRequestLogger::class)->log(
                $this->serviceRequest,
                ServiceRequestLogAction::EmailFailed,
                "{$this->failureDescriptionPrefix}: {$exception->getMessage()}",
            );
        }
    }
}
