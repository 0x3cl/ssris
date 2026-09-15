<?php

namespace Database\Seeders;

use App\Enums\FormTemplateKey;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class FormTemplateSeeder extends Seeder
{
    public function run(): void
    {
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
                'subject' => 'Customer Satisfaction Feedback',
                'variables' => ['reference_no', 'due', 'link'],
                'body' => $this->paragraphs([
                    'Good Day!',
                    'Please be advised that your service request with Reference Number: {{reference_no}} due on {{due}} is ready for pick-up. Please be reminded to fill out this Customer Satisfaction Feedback to claim the products sample or results of your service request.',
                    'Customer Satisfaction Feedback form link: {{link}}',
                    'Thank you so much.',
                    "Sincerely yours,\nPTRI RDD Receiving Officer",
                ]),
            ],
            [
                'key' => FormTemplateKey::PaymentReminder,
                'subject' => 'Payment Reminder for Your Service Request',
                'variables' => ['reference_no', 'amount', 'due'],
                'body' => $this->paragraphs([
                    'Good Day!',
                    'Please be advised that your service request with Reference Number: {{reference_no}} is now ready for payment. Kindly settle the amount of {{amount}} on or before {{due}} so we can proceed with the processing of your request.',
                    "You may present this notice at the PTRI Cashier's Office during office hours.",
                    'Thank you so much.',
                    "Sincerely yours,\nPTRI RDD Receiving Officer",
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentVerified,
                'subject' => 'Your Appointment Has Been Verified',
                'variables' => ['reference_no', 'schedule', 'service'],
                'body' => $this->paragraphs([
                    'Good Day!',
                    'Please be advised that your appointment for {{service}} with Reference Number: {{reference_no}} has been verified and is confirmed on {{schedule}}.',
                    'Kindly arrive at the PTRI Receiving Office at least fifteen (15) minutes before your scheduled time and bring a valid identification card.',
                    'Thank you so much.',
                    "Sincerely yours,\nPTRI RDD Receiving Officer",
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentCancellation,
                'subject' => 'Your Appointment Has Been Cancelled',
                'variables' => ['reference_no', 'schedule', 'reason'],
                'body' => $this->paragraphs([
                    'Good Day!',
                    'Please be advised that your appointment with Reference Number: {{reference_no}} scheduled on {{schedule}} has been cancelled.',
                    'Reason for cancellation: {{reason}}',
                    'You may book another appointment at your most convenient schedule through our online booking page.',
                    'Thank you so much.',
                    "Sincerely yours,\nPTRI RDD Receiving Officer",
                ]),
            ],
            [
                'key' => FormTemplateKey::AppointmentReschedule,
                'subject' => 'Your Appointment Has Been Rescheduled',
                'variables' => ['reference_no', 'old_schedule', 'new_schedule', 'reason'],
                'body' => $this->paragraphs([
                    'Good Day!',
                    'Please be advised that your appointment with Reference Number: {{reference_no}} originally scheduled on {{old_schedule}} has been moved to {{new_schedule}}.',
                    'Reason for rescheduling: {{reason}}',
                    'Kindly arrive at the PTRI Receiving Office at least fifteen (15) minutes before your new scheduled time and bring a valid identification card.',
                    'Thank you so much.',
                    "Sincerely yours,\nPTRI RDD Receiving Officer",
                ]),
            ],
        ];
    }

    /**
     * Join the given paragraphs into the HTML the rich text editor expects,
     * turning any internal newline (e.g. a signature block) into a line break.
     *
     * @param  array<int, string>  $paragraphs
     */
    private function paragraphs(array $paragraphs): string
    {
        return Collection::make($paragraphs)
            ->map(fn (string $paragraph): string => '<p>'.str_replace("\n", '<br>', $paragraph).'</p>')
            ->implode('');
    }
}
