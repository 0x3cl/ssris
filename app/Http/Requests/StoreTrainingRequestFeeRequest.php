<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingRequestFeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_time' => ['required', 'date'],
            'particulars' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:255'],
            'no_participants' => ['required', 'integer', 'min:1'],
            'net_amount_due' => ['required', 'numeric', 'min:0'],
        ];
    }
}
