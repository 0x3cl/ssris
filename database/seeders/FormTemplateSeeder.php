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
                'subject' => 'SRIS: Feedback Form Reminder',
                'variables' => ['reference', 'due_date', 'link'],
                'body' => $this->blocks([
                    $this->lines(['Good Day!']),
                    $this->lines([
                        'Please be advised that your service request with Reference Number: {{reference}}',
                        'due on {{due_date}} is ready for pick-up.',
                    ]),
                    $this->lines([
                        'Please be reminded to fill out this Customer Satisfaction Feedback to claim',
                        'the products sample or results of your service request.',
                    ]),
                    $this->lines([
                        'Customer Satisfaction Feedback form link:',
                        '{{link}}',
                    ]),
                    $this->lines(['Thank you so much.']),
                    $this->lines(['Sincerely yours,', 'Receiving Officer']),
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
                'subject' => 'SRIS: Confirmed Appointment',
                'variables' => ['appointment_id', 'confirmed_date', 'confirmed_time', 'service', 'client_email', 'client_name', 'mobile_number'],
                'body' => $this->blocks([
                    $this->lines(['Good Day!']),
                    $this->lines([
                        'This is to confirm your schedule appointment with Philippine Textile Research Institute.',
                        'Listed below is the official summary of your Appointment for your reference.',
                        'Please create a copy of this to present to our guard in your scheduled appointment.',
                        'You may either screenshot this, save as PDF, print a copy, or show this email message.',
                    ]),
                    $this->bulletList([
                        'Appointment ID: {{appointment_id}}',
                        'Date of Appointment: {{confirmed_date}}',
                        'Time of Appointment: {{confirmed_time}}',
                        'Type of Service: {{service}}',
                        'Email: {{client_email}}',
                        'Name: {{client_name}}',
                        'Mobile Number: {{mobile_number}}',
                    ]),
                    $this->lines(['Thank you so much.']),
                    $this->lines(['Sincerely yours,', 'Receiving Officer']),
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentCancellation,
                'subject' => 'SRIS: Appointment Cancelled',
                'variables' => ['appointment_id', 'service', 'appointment_date', 'appointment_time', 'client_email', 'client_name', 'administrator', 'reason'],
                'body' => $this->blocks([
                    $this->lines(['Good Day!']),
                    $this->lines([
                        'This is to confirm the cancellation of your Appointment Request for your reference.',
                        'Details are listed below:',
                    ]),
                    $this->bulletList([
                        'Appointment ID: {{appointment_id}}',
                        'Type of Service: {{service}}',
                        'Date of Appointment: {{appointment_date}}',
                        'Time of Appointment: {{appointment_time}}',
                        'Email: {{client_email}}',
                        'Name: {{client_name}}',
                        'Reason for Cancellation: {{reason}}',
                    ]),
                    $this->lines(['We are looking forward to our future transactions. Thank you.']),
                    $this->lines(['Sincerely Yours,', '{{administrator}}']),
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentReschedule,
                'subject' => 'SRIS: Re-scheduled Appointment',
                'variables' => ['previous_date', 'previous_time', 'new_date', 'new_time', 'service', 'client_email', 'client_name', 'mobile_number', 'reason'],
                'body' => $this->blocks([
                    $this->lines(['Good Day!']),
                    $this->lines([
                        'This is to confirm your new scheduled appointment with Philippine Textile Research Institute.',
                        'Your request has been moved from your previous schedule to this new date & time.',
                        'Listed below is the official summary of your new Appointment for your reference.',
                        'Please create a copy of this to present to our guard in your scheduled appointment.',
                        'You may either screenshot this, save as PDF, print a copy, or show this email message.',
                    ]),
                    $this->bulletList([
                        'Previous Date of Appointment: {{previous_date}}',
                        'Previous Time of Appointment: {{previous_time}}',
                        'Date of Appointment: {{new_date}}',
                        'Time of Appointment: {{new_time}}',
                        'Type of Service: {{service}}',
                        'Email: {{client_email}}',
                        'Name: {{client_name}}',
                        'Mobile Number: {{mobile_number}}',
                        'Reason for Rescheduling: {{reason}}',
                    ]),
                    $this->lines(['Thank you so much for your patience.']),
                    $this->lines(['Sincerely Yours,', 'Receiving Officer']),
                ]),
            ],
            [
                'key' => FormTemplateKey::ServiceRequestReceipt,
                'subject' => 'SRIS: Service Request',
                'variables' => ['service', 'client_email', 'client_name', 'mobile_number'],
                'body' => $this->blocks([
                    $this->lines(['Good Day!']),
                    $this->lines(['This is to acknowledge receipt of your Service Request of the following:']),
                    $this->bulletList([
                        'Type of Service: {{service}}',
                        'Email: {{client_email}}',
                        'Name: {{client_name}}',
                        'Mobile Number: {{mobile_number}}',
                    ]),
                    $this->lines(['Our assigned receiving officer will send you the details of your service request.']),
                    $this->lines(['Thank you so much.']),
                    $this->lines(['Sincerely yours,', 'Receiving Officer']),
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
