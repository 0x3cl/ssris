<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequestRequest extends FormRequest
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
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'test_category' => ['required', 'string', 'max:255'],
            'ulims_test_category_id' => ['nullable', 'integer'],
            'sample_type' => ['required', 'string', 'max:255'],
            'ulims_sample_type_id' => ['nullable', 'integer'],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.test' => ['required', 'string', 'max:255'],
            'items.*.ulims_test_id' => ['nullable', 'integer'],
            'items.*.method' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_fee' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            $subTotal = collect($items)->sum(
                fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_fee'] ?? 0)
            );

            if ($subTotal <= 0) {
                $validator->errors()->add('total', 'The total amount cannot be 0. Add at least one item with a quantity and unit fee.');
            }
        });
    }
}
