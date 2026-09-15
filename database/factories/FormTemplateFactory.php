<?php

namespace Database\Factories;

use App\Enums\FormTemplateKey;
use App\Models\FormTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormTemplate>
 */
class FormTemplateFactory extends Factory
{
    protected $model = FormTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->randomElement(FormTemplateKey::cases());

        return [
            'key' => $key,
            'name' => $key->label(),
            'subject' => fake()->sentence(),
            'body' => "Good Day!\n\n".fake()->paragraph()." Reference Number: {{reference_no}}.\n\nSincerely yours,\nPTRI RDD Receiving Officer",
            'variables' => ['reference_no'],
        ];
    }

    public function key(FormTemplateKey $key): static
    {
        return $this->state(fn (): array => ['key' => $key, 'name' => $key->label()]);
    }
}
