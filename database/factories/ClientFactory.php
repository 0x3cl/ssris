<?php

namespace Database\Factories;

use App\Enums\ClientService;
use App\Enums\ClientSource;
use App\Enums\ClientType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Client> */
class ClientFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $firstname = fake()->firstName();
        $lastname = fake()->lastName();

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'fullname' => $firstname.' '.$lastname,
            'email' => fake()->unique()->safeEmail(),
            'mobile_no' => fake()->phoneNumber(),
            'age' => fake()->numberBetween(18, 90),
            'gender' => 'female',
            'address' => fake()->streetAddress(),
            'region' => 'Region IV-A',
            'province' => 'Laguna',
            'municipality' => 'Los Baños',
            'tel_no' => fake()->phoneNumber(),
            'type_client' => ClientType::Individual->value,
            'source' => ClientSource::Internet->value,
            'service' => ClientService::RddServices->value,
            'description' => fake()->sentence(),
        ];
    }
}
