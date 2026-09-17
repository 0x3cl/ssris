<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdateTourScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('requests.write') ?? false;
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['visit_date', 'visit_time'])) {
                return;
            }

            if (Carbon::parse($this->input('visit_date').' '.$this->input('visit_time'))->isPast()) {
                $validator->errors()->add('visit_time', 'The proposed schedule must be in the future.');
            }
        }];
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'visit_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'visit_time' => ['required', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
