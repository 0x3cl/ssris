<?php

namespace App\Services;

use App\Enums\FormTemplateKey;
use App\Jobs\SendQueuedServiceMail;
use App\Mail\FormTemplateMail;
use App\Models\FormTemplate;
use App\Models\ServiceRequest;
use App\Models\SmtpSetting;

class FormTemplateMailer
{
    /**
     * Render the given form template with the supplied values and queue it for delivery to the recipient.
     *
     * @param  array<string, string|int|float|null>  $values
     * @return string|null An error message if the email could not be queued, or null once it is queued.
     */
    public function send(FormTemplateKey $key, string $recipientEmail, array $values, ?ServiceRequest $serviceRequest = null): ?string
    {
        $template = FormTemplate::query()->where('key', $key)->first();

        if ($template === null) {
            return "The \"{$key->label()}\" email template is not configured.";
        }

        if (SmtpSetting::query()->doesntExist()) {
            return 'Configure SMTP settings before sending emails.';
        }

        $rendered = $template->render($values);

        SendQueuedServiceMail::dispatch(
            new FormTemplateMail($rendered['subject'], $rendered['body']),
            $recipientEmail,
            $serviceRequest,
            "\"{$key->label()}\" email sent to the client.",
            "\"{$key->label()}\" email could not be sent",
        );

        return null;
    }
}
