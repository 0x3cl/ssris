<?php

namespace App\Services;

use App\Enums\ClientBusinessRole;
use App\Enums\ClientEnterpriseSize;
use App\Enums\ClientMarket;
use App\Enums\ClientService as ClientServiceType;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ClientService
{
    /** @return LengthAwarePaginator<int, Client> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Client::query()->orderByDesc('id')->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): Client
    {
        return Client::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Client
    {
        $client = new Client;
        $client->forceFill($this->validate($data));
        $client->save();

        return $client;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): Client
    {
        $client = $this->find($id);

        $client->forceFill($this->validate($data, $client));

        $client->save();

        return $client;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->find($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?Client $client = null): array
    {
        $presence = $client ? 'sometimes' : 'required';

        return Validator::make($data, [
            'firstname' => [$presence, 'required', 'string', 'max:255'],
            'middlename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lastname' => [$presence, 'required', 'string', 'max:255'],
            'fullname' => [$presence, 'required', 'string', 'max:255'],
            'email' => [$presence, 'required', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client)],
            'mobile_no' => [$presence, 'required', 'string', 'max:255'],
            'fax_no' => ['sometimes', 'nullable', 'string', 'max:255'],
            'age' => [$presence, 'required', 'integer', 'min:0'],
            'gender' => [$presence, 'required', 'string', 'max:255'],
            'address' => [$presence, 'required', 'string', 'max:255'],
            'region' => [$presence, 'required', 'string', 'max:255'],
            'province' => [$presence, 'required', 'string', 'max:255'],
            'municipality' => [$presence, 'required', 'string', 'max:255'],
            'tel_no' => [$presence, 'required', 'string', 'max:255'],
            'type_client' => [$presence, 'required', Rule::enum(ClientType::class)],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'business_role' => ['sometimes', 'nullable', Rule::enum(ClientBusinessRole::class)],
            'enterprise_size' => ['sometimes', 'nullable', Rule::enum(ClientEnterpriseSize::class)],
            'market' => ['sometimes', 'nullable', Rule::enum(ClientMarket::class)],
            'products' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source' => [$presence, 'required', Rule::enum(ClientSource::class)],
            'service' => [$presence, 'required', Rule::enum(ClientServiceType::class)],
            'description' => [$presence, 'required', 'string'],
        ])->validate();
    }
}
