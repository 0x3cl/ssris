<?php

namespace App\Console\Commands;

use App\Enums\ClientService;
use App\Enums\OtherTourFacility;
use App\Enums\PilotPlantFacility;
use App\Enums\TestingLabFacility;
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
    {--training : Generate a Training Services request}
    {--tour : Generate a Plant Tour Services request}
    {--library : Generate a Library Registration request}
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

            return collect(range(1, $amount))->map(function () use ($client, $service, $isAppointment): ServiceRequest {
                $serviceRequest = ServiceRequest::factory()
                    ->for($client)
                    ->create([
                        'service' => $service,
                        'is_appointment' => $isAppointment,
                        'appointment_date' => $isAppointment ? today()->addWeek()->toDateString() : null,
                        'appointment_time' => $isAppointment ? '09:00:00' : null,
                        ...($service === ClientService::PlantTourServices ? ['description' => null] : []),
                    ]);

                if ($service === ClientService::PlantTourServices) {
                    $tourRequest = $serviceRequest->tourRequest()->create([
                        'visit_date' => today()->addWeek()->toDateString(),
                        'visit_time' => '09:00:00',
                        'message' => 'We would like to arrange an educational tour of the textile facilities.',
                        'no_persons' => 20,
                        'no_groups' => 2,
                        'technology_assistance' => 'Introduction to textile testing and fabric production.',
                        'visit_objectives' => 'Learn about textile research, testing, and pilot plant operations.',
                    ]);
                    $tourRequest->items()->create([
                        'testing_lab' => [TestingLabFacility::Physical->value, TestingLabFacility::Chemical->value],
                        'pilot_plant' => [PilotPlantFacility::Spinning->value, PilotPlantFacility::Weaving->value],
                        'others' => [OtherTourFacility::TelaGallery->value],
                    ]);
                }

                return $serviceRequest;
            });
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
            'training' => ClientService::TrainingServices,
            'tour' => ClientService::PlantTourServices,
            'library' => ClientService::LibraryRegistration,
        ];
        $selectedServices = array_filter(
            $services,
            fn (ClientService $service, string $option): bool => (bool) $this->option($option),
            ARRAY_FILTER_USE_BOTH,
        );

        if (count($selectedServices) !== 1) {
            $this->components->error('Choose exactly one service: --rdd, --lab, --processing, --training, --tour, or --library.');

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
