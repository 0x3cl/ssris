<?php

namespace App\Http\Requests;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkInRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile_no' => ['required', 'string', 'max:30'],
            'fax_no' => ['nullable', 'string', 'max:30'],
            'age' => ['required', 'integer', 'between:0,120'],
            'gender' => ['required', Rule::in(['male', 'female', 'prefer-not-to-say'])],
            'address' => ['required', 'string', 'max:1000'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'tel_no' => ['required', 'string', 'max:30'],
            'type_client' => ['required', Rule::enum(ClientType::class)],
            'company' => ['nullable', 'required_if:type_client,government,private-companies', 'string', 'max:255'],
            'school_name' => ['nullable', 'required_if:type_client,academe', 'string', 'max:255'],
            'business_role' => ['nullable', 'required_if:type_client,private-companies', Rule::enum(ClientBusinessRole::class)],
            'enterprise_size' => ['nullable', 'required_if:type_client,private-companies', Rule::enum(ClientEnterpriseSize::class)],
            'market' => ['nullable', 'required_if:type_client,private-companies', Rule::enum(ClientMarket::class)],
            'products' => ['nullable', 'required_if:type_client,private-companies', 'string', 'max:255'],
            'source' => ['required', Rule::enum(ClientSource::class)],
            'service' => ['required', Rule::enum(ClientService::class)],
            'description' => ['required', 'string'],
        ];

        if (! $this->routeIs('walk-in.validate')) {
            $rules['terms_accepted'] = ['accepted'];
        }

        return $rules;
    }
}
