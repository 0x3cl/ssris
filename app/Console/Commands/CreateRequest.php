<?php

namespace App\Console\Commands;

use App\Enums\ClientService;
use App\Models\Client;
use App\Models\ServiceRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('create-request
    {--walkin : Generate a walk-in request}
    {--appointment : Generate an appointment request}
    {--rdd : Generate an R&D Services request}
    {--lab : Generate a Lab Services request}
    {--processing : Generate a Processing Services request}
    {--amount=1 : Amount of requests to generate}')]
#[Description('Generate a sample service request')]
class CreateRequest extends Command
{
    private const CLIENT_EMAIL = 'iamcarlllemos@gmail.com';

    public function handle(): int
    {
        $isAppointment = $this->selectedRequestType();
        $service = $this->selectedService();
        $amount = $this->selectedAmount();

        if ($isAppointment === null || $service === null || $amount === null) {
            return self::FAILURE;
        }

        $serviceRequests = DB::transaction(function () use ($isAppointment, $service, $amount) {
            $client = Client::withTrashed()->firstOrCreate(
                ['email' => self::CLIENT_EMAIL],
                Client::factory()->raw(['email' => self::CLIENT_EMAIL, 'service' => $service]),
            );
            if ($client->trashed()) {
                $client->is_deleted = false;
                $client->restore();
            }

            return collect(range(1, $amount))->map(fn (): ServiceRequest => ServiceRequest::factory()
                ->for($client)
                ->create([
                    'service' => $service,
                    'is_appointment' => $isAppointment,
                    'appointment_date' => $isAppointment ? today()->addWeek()->toDateString() : null,
                    'appointment_time' => $isAppointment ? '09:00:00' : null,
                ]));
        });
        $serviceRequests->each->load('client');

        $this->components->info($amount === 1 ? 'Service request created successfully.' : "{$amount} service requests created successfully.");
        $this->table(
            ['Request ID', 'Type', 'Service', 'Client email'],
            $serviceRequests->map(fn (ServiceRequest $serviceRequest): array => [
                $serviceRequest->id,
                $isAppointment ? 'Appointment' : 'Walk-in',
                $service->label(),
                $serviceRequest->client->email,
            ])->all(),
        );

        return self::SUCCESS;
    }

    private function selectedRequestType(): ?bool
    {
        $walkIn = $this->option('walkin');
        $appointment = $this->option('appointment');

        if ($walkIn === $appointment) {
            $this->components->error('Choose exactly one request type: --walkin or --appointment.');

            return null;
        }

        return $appointment;
    }

    private function selectedService(): ?ClientService
    {
        $services = [
            'rdd' => ClientService::RddServices,
            'lab' => ClientService::LabServices,
            'processing' => ClientService::ProcessingServices,
        ];
        $selectedServices = array_filter(
            $services,
            fn (ClientService $service, string $option): bool => (bool) $this->option($option),
            ARRAY_FILTER_USE_BOTH,
        );

        if (count($selectedServices) !== 1) {
            $this->components->error('Choose exactly one service: --rdd, --lab, or --processing.');

            return null;
        }

        return array_values($selectedServices)[0];
    }

    private function selectedAmount(): ?int
    {
        $amount = (int) $this->option('amount');

        if ($amount < 1) {
            $this->components->error('--amount must be at least 1.');

            return null;
        }

        return $amount;
    }
}
