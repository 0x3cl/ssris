<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRddRequestRequest extends FormRequest
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
            'reference_prefix' => ['required', Rule::in(['CDABUS', 'NFUS'])],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item' => ['required', 'string', 'max:255'],
            'items.*.specification' => ['required', 'string'],
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
