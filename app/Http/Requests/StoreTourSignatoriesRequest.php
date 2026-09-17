<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTourSignatoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('requests.write') ?? false;
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'prepared_by' => ['required', 'string', 'max:255'],
            'noted_by' => ['required', 'string', 'max:255'],
            'conforme_primary' => ['required', 'string', 'max:255'],
            'conforme_secondary' => ['required', 'string', 'max:255'],
            'conforme_optional' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
