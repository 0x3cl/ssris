<?php

namespace App\Http\Requests;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = [
            'appointment_date' => ['required', 'date', 'after:today'],
            'appointment_time' => ['required', 'date_format:H:i'],
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

        if (! $this->routeIs('appointment.validate')) {
            $rules['terms_accepted'] = ['accepted'];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->routeIs('appointment.store') && $validator->errors()->has('email')) {
            throw new HttpResponseException(
                to_route('home')->with('error', 'Please start again with a valid email address.'),
            );
        }

        parent::failedValidation($validator);
    }
}
