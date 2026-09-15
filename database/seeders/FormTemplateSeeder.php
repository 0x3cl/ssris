<?php

namespace Database\Seeders;

use App\Enums\FormTemplateKey;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FormTemplateSeeder extends Seeder
{
    /** A blank editor line, used to put visible space between blocks of content. */
    private const SPACER = '<p><br></p>';

    public function run(): void
    {
        // Renamed from "Appointment Verified" to "Appointment Confirmed".
        FormTemplate::query()
            ->where('key', 'appointment-verified')
            ->update(['key' => FormTemplateKey::AppointmentConfirmed->value]);

        foreach ($this->templates() as $template) {
            FormTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['key']->label(),
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'variables' => $template['variables'],
                ],
            );
        }
    }

    /**
     * @return array<int, array{key: FormTemplateKey, subject: string, body: string, variables: array<int, string>}>
     */
    private function templates(): array
    {
        return [
            [
                'key' => FormTemplateKey::FeedbackReminder,
                'subject' => 'SRIS: Customer Satisfaction Feedback',
                'variables' => ['name', 'reference_no', 'due', 'link'],
                'body' => $this->message('Good Day {{name}}!', [
                    'Please be advised that your service request with Reference Number: {{reference_no}} due on {{due}} is ready for pick-up. Please be reminded to fill out this Customer Satisfaction Feedback to claim the products sample or results of your service request.',
                    'Customer Satisfaction Feedback form link: {{link}}',
                    'Thank you so much.',
                ]),
            ],
            [
                'key' => FormTemplateKey::PaymentReminder,
                'subject' => 'SRIS: Payment Reminder for Your Service Request',
                'variables' => ['name', 'reference_no', 'amount', 'due'],
                'body' => $this->message('Good Day {{name}}!', [
                    'Please be advised that your service request with Reference Number: {{reference_no}} is now ready for payment. Kindly settle the amount of {{amount}} on or before {{due}} so we can proceed with the processing of your request.',
                    "You may present this notice at the PTRI Cashier's Office during office hours.",
                    'Thank you so much.',
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentConfirmed,
                'subject' => 'SRIS: Your Appointment Has Been Confirmed',
                'variables' => ['name', 'reference_no', 'schedule', 'service'],
                'body' => $this->message('Good Day {{name}}!', [
                    'Please be advised that your appointment for {{service}} with Reference Number: {{reference_no}} has been confirmed on {{schedule}}.',
                    'Kindly arrive at the PTRI Receiving Office at least fifteen (15) minutes before your scheduled time and bring a valid identification card.',
                    'Thank you so much.',
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentCancellation,
                'subject' => 'SRIS: Your Appointment Has Been Cancelled',
                'variables' => ['name', 'reference_no', 'schedule', 'reason'],
                'body' => $this->message('Good Day {{name}}!', [
                    'Please be advised that your appointment with Reference Number: {{reference_no}} scheduled on {{schedule}} has been cancelled.',
                    'Reason for cancellation: {{reason}}',
                    'You may book another appointment at your most convenient schedule through our online booking page.',
                    'Thank you so much.',
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentReschedule,
                'subject' => 'SRIS: Your Appointment Has Been Rescheduled',
                'variables' => ['name', 'reference_no', 'old_schedule', 'new_schedule', 'reason'],
                'body' => $this->message('Good Day {{name}}!', [
                    'Please be advised that your appointment with Reference Number: {{reference_no}} originally scheduled on {{old_schedule}} has been moved to {{new_schedule}}.',
                    'Reason for rescheduling: {{reason}}',
                    'Kindly arrive at the PTRI Receiving Office at least fifteen (15) minutes before your new scheduled time and bring a valid identification card.',
                    'Thank you so much.',
                ]),
            ],
            [
                'key' => FormTemplateKey::ServiceRequestReceipt,
                'subject' => 'SRIS: Service Request',
                'variables' => [
                    'name', 'client_email', 'client_name', 'mobile_number', 'participant_count',
                    'group_count', 'technology_assistance', 'request_message',
                ],
                'body' => $this->blocks([
                    $this->lines(['Good Day {{name}}!']),
                    $this->lines(['This is to acknowledge receipt of your Plant Tour Service Request of the following:']),
                    $this->bulletList([
                        'Email: {{client_email}}',
                        'Name: {{client_name}}',
                        'Mobile Number: {{mobile_number}}',
                        'Number of Persons: {{participant_count}}',
                        'Number of Group(s)/Batch(es): {{group_count}}',
                        'Technology Assistance: {{technology_assistance}}',
                        'Your message to us: {{request_message}}',
                    ]),
                    $this->lines([
                        'Our assigned receiving officer will send you the details of your Tour.',
                        'Thank you so much.',
                    ]),
                    $this->lines(['Sincerely yours,', 'Receiving Officer.']),
                ]),
            ],
            [
                'key' => FormTemplateKey::TestNotification,
                'subject' => 'SRIS: Test Notification',
                'variables' => ['name', 'sent_at'],
                'body' => $this->message('Good Day {{name}}!', [
                    'This is a test notification from the Service Requests Information System (SRIS), sent on {{sent_at}} to confirm that outgoing email notifications are working correctly.',
                    'If you received this message, the SMTP configuration is set up properly and no further action is needed.',
                    'Thank you so much.',
                ]),
            ],
        ];
    }

    /**
     * Build a standard notice: a greeting line, a blank line, the body lines packed
     * tightly together (no gaps between them), a blank line, then the sign-off —
     * "Sincerely yours," and "Receiving Officer" as two adjacent lines.
     *
     * @param  array<int, string>  $bodyLines
     */
    private function message(string $greeting, array $bodyLines): string
    {
        return $this->blocks([
            $this->lines([$greeting]),
            $this->lines($bodyLines),
            $this->lines(['Sincerely yours,', 'Receiving Officer']),
        ]);
    }

    /**
     * Join pre-built blocks (each from lines()/bulletList()) with a blank
     * editor line between them, so sections read as separate paragraphs.
     *
     * @param  array<int, string>  $blocks
     */
    private function blocks(array $blocks): string
    {
        return implode(self::SPACER, $blocks);
    }

    /**
     * Turn a set of lines into adjacent `<p>` elements with no blank line
     * between them, so they read as one tightly packed block.
     *
     * @param  array<int, string>  $lines
     */
    private function lines(array $lines): string
    {
        return Collection::make($lines)
            ->map(fn (string $line): string => '<p>'.$line.'</p>')
            ->implode('');
    }

    /**
     * Join the given lines into a properly indented HTML bullet list, for
     * blocks that read as a set of "Label: value" rows.
     *
     * @param  array<int, string>  $items
     */
    private function bulletList(array $items): string
    {
        $rows = Collection::make($items)
            ->map(fn (string $item): string => '<li>'.$item.'</li>')
            ->implode('');

        return "<ul>{$rows}</ul>";
    }
}
