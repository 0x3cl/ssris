<?php

namespace App\Http\Requests;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Enums\OtherTourFacility;
use App\Enums\PilotPlantFacility;
use App\Enums\TestingLabFacility;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
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
            'age' => ['required', 'integer', 'between:6,80'],
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
            'description' => ['nullable', 'required_unless:service,plant-tour-services', 'string'],
            'visit_date' => ['nullable', 'required_if:service,plant-tour-services', 'date', 'after:today'],
            'visit_time' => ['nullable', 'required_if:service,plant-tour-services', 'date_format:H:i'],
            'message' => ['nullable', 'required_if:service,plant-tour-services', 'string'],
            'no_persons' => ['nullable', 'required_if:service,plant-tour-services', 'integer', 'min:1'],
            'no_groups' => ['nullable', 'required_if:service,plant-tour-services', 'integer', 'min:1'],
            'technology_assistance' => ['nullable', 'required_if:service,plant-tour-services', 'string', 'max:2000'],
            'visit_objectives' => ['nullable', 'required_if:service,plant-tour-services', 'string', 'max:2000'],
            'testing_lab' => ['nullable', 'array'],
            'testing_lab.*' => [Rule::enum(TestingLabFacility::class)],
            'pilot_plant' => ['nullable', 'array'],
            'pilot_plant.*' => [Rule::enum(PilotPlantFacility::class)],
            'others' => ['nullable', 'array'],
            'others.*' => [Rule::enum(OtherTourFacility::class)],
        ];

        if (! $this->routeIs('walk-in.validate')) {
            $rules['terms_accepted'] = ['accepted'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('service') !== ClientService::PlantTourServices->value) {
                return;
            }

            $hasFacility = collect(['testing_lab', 'pilot_plant', 'others'])
                ->contains(fn (string $field): bool => filled($this->input($field)));

            if (! $hasFacility) {
                $validator->errors()->add('facilities', 'Choose at least one facility you want to visit.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->routeIs('walk-in.store') && $validator->errors()->has('email')) {
            throw new HttpResponseException(
                to_route('home')->with('error', 'Please start again with a valid email address.'),
            );
        }

        parent::failedValidation($validator);
    }
}
