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
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'fullname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile_no' => ['required', 'string', 'max:255'],
            'fax_no' => ['nullable', 'string', 'max:255'],
            'age' => ['required', 'integer', 'min:0'],
            'gender' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:255'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['required', 'string', 'max:255'],
            'tel_no' => ['required', 'string', 'max:255'],
            'type_client' => ['required', Rule::enum(ClientType::class)],
            'company' => ['nullable', 'string', 'max:255'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'business_role' => ['nullable', Rule::enum(ClientBusinessRole::class)],
            'enterprise_size' => ['nullable', Rule::enum(ClientEnterpriseSize::class)],
            'market' => ['nullable', Rule::enum(ClientMarket::class)],
            'products' => ['nullable', 'string', 'max:255'],
            'source' => ['required', Rule::enum(ClientSource::class)],
            'service' => ['required', Rule::enum(ClientService::class)],
            'description' => ['required', 'string'],
        ];
    }
}
