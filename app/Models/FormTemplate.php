<?php

namespace App\Models;

use App\Enums\FormTemplateKey;
use Database\Factories\FormTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['key', 'name', 'subject', 'body', 'variables'])]
class FormTemplate extends Model implements Auditable
{
    /** @use HasFactory<FormTemplateFactory> */
    use AuditableTrait, HasFactory;

    /**
     * Replace every `{{ placeholder }}` in the subject and body with the given values.
     *
     * The subject is plain text, so values are substituted as-is. The body is
     * HTML, so values are HTML-escaped to stop a value (e.g. a client name)
     * from injecting markup into the rendered message.
     *
     * @param  array<string, string|int|float|null>  $values
     * @return array{subject: string, body: string}
     */
    public function render(array $values): array
    {
        $rawReplacements = [];
        $escapedReplacements = [];

        foreach ($values as $placeholder => $value) {
            $value = (string) $value;
            $rawReplacements["{{{$placeholder}}}"] = $value;
            $rawReplacements["{{ {$placeholder} }}"] = $value;
            $escapedReplacements["{{{$placeholder}}}"] = e($value);
            $escapedReplacements["{{ {$placeholder} }}"] = e($value);
        }

        return [
            'subject' => strtr($this->subject, $rawReplacements),
            'body' => strtr($this->body, $escapedReplacements),
        ];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'key' => FormTemplateKey::class,
            'variables' => 'array',
        ];
    }
}
