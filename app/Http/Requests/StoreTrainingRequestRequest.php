<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRequestRequest extends FormRequest
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
            'training_course_requested' => ['required', 'string', 'max:255'],
            'estimated_participants' => ['required', 'integer', 'min:1'],
            'proposed_training_date' => ['required', 'date', 'after_or_equal:today'],
            'proposed_training_venue' => ['required', 'string', 'max:255'],
            'beneficiary_name' => ['required', 'string', 'max:255'],
            'purpose_of_training' => ['required', 'string'],
            'assigned_trainer' => ['required', 'string', 'max:255'],
            'assigned_assistant_trainer' => ['nullable', 'string', 'max:255'],
            'official_course_title' => ['required', 'string', 'max:255'],
            'approved_training_duration' => ['required', 'string', 'max:255'],
            'training_type' => ['required', Rule::in(['in-house', 'regional', 'virtual'])],
            'special_type_details' => ['nullable', 'string', 'max:255'],
        ];
    }
}
