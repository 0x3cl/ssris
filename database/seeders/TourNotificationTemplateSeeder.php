<?php

namespace Database\Seeders;

use App\Enums\FormTemplateKey;
use App\Models\FormTemplate;
use Illuminate\Database\Seeder;

class TourNotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $messages = [
            FormTemplateKey::TourAccepted->value => 'Your plant tour request has been accepted. Your confirmed schedule is {{visit_date}} at {{visit_time}}.',
            FormTemplateKey::TourRescheduled->value => 'A new schedule has been proposed for your plant tour: {{visit_date}} at {{visit_time}}. Previous schedule: {{previous_date}} at {{previous_time}}. Reason: {{reason}}. Your request remains pending acceptance.',
            FormTemplateKey::TourCancelled->value => 'Your plant tour scheduled for {{visit_date}} at {{visit_time}} has been cancelled. Reason: {{reason}}.',
        ];

        foreach ($messages as $value => $message) {
            $key = FormTemplateKey::from($value);
            FormTemplate::query()->firstOrCreate(['key' => $key], [
                'name' => $key->label(),
                'subject' => 'SRRIS: '.$key->label(),
                'body' => '<p>Good day {{client_name}},</p><p>'.$message.'</p><p>Service request: {{reference_no}}</p><p>Thank you.<br>Receiving Officer</p>',
                'variables' => ['client_name', 'reference_no', 'visit_date', 'visit_time', 'previous_date', 'previous_time', 'reason'],
            ]);
        }
    }
}
