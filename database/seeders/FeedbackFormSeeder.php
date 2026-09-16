<?php

namespace Database\Seeders;

use App\Models\FeedbackDimension;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackRating;
use Illuminate\Database\Seeder;

class FeedbackFormSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->ratings() as $rating) {
            FeedbackRating::query()->updateOrCreate(
                ['value' => $rating['value']],
                ['name' => $rating['name']],
            );
        }

        foreach ($this->dimensions() as $name => $items) {
            $dimension = FeedbackDimension::query()->firstOrCreate(['name' => $name]);

            foreach ($items as $description) {
                $dimension->items()->firstOrCreate(['description' => $description]);
            }
        }

        foreach ($this->questions() as $question) {
            FeedbackQuestion::query()->firstOrCreate(['name' => $question]);
        }
    }

    /** @return array<int, array{value: string, name: string}> */
    private function ratings(): array
    {
        return [
            ['value' => '5', 'name' => 'Excellent'],
            ['value' => '4', 'name' => 'Very Satisfactory'],
            ['value' => '3', 'name' => 'Satisfactory'],
            ['value' => '2', 'name' => 'Fair'],
            ['value' => '1', 'name' => 'Poor'],
            ['value' => 'N/A', 'name' => 'Not Applicable'],
        ];
    }

    /** @return array<string, array<int, string>> */
    private function dimensions(): array
    {
        return [
            'Responsiveness' => [
                "Prompt, and courteous response to customer's request",
                "Appropriate response to customer's request",
                'Flexibility to address concerns of the customer',
            ],
            'Reliability' => [
                'Timeliness of services provided',
                'Quality of services provided',
                'Quality of Product',
            ],
            'Access' => [
                'Physical accessibility',
                'Online dissemination of information via website',
            ],
            'Facilities' => [
                'Availability of elevator, ramp for the differently-abled person, etc',
                'Cleanliness of the facilities',
                'Safety of the facilities',
                'Well ventilated facilities',
            ],
            'Communication' => [
                'Clarity of information on the services rendered',
                'Form is easy to understand/follow',
                'Clearly explain the test results and sample products provided',
            ],
            'Costs' => [
                'No hidden fees or additional charges',
            ],
            'Integrity' => [
                "Transparency of the transactions/procedures or adherence to the Citizen's Charter (e.g. First Come, First Serve Policy, No Noon Break)",
                'Protection of confidential information (Data Privacy Act)',
                'Adherence to Civil Service Code of Ethics and Ethical Standards',
            ],
            'Assurance' => [
                'Staff is knowledgeable of the process and other relevant information',
                'Staff appears neat and professional',
            ],
            'Outcome' => [
                'Satisfied with the overall service provided',
            ],
            'Net Promoter Score' => [
                'How likely is it that you would recommend our service to others?',
            ],
        ];
    }

    /** @return array<int, string> */
    private function questions(): array
    {
        return [
            'Areas for improvement',
            'Other comments/suggestions',
        ];
    }
}
