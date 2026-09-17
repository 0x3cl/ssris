<?php

namespace Tests\Feature;

use App\Enums\ClientService;
use App\Enums\ServiceRequestStatus;
use App\Jobs\SendQueuedServiceMail;
use App\Models\ServiceRequest;
use App\Models\SmtpSetting;
use App\Models\TourRequest;
use App\Models\User;
use Database\Seeders\TourNotificationTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TourRequestActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_a_tour_queues_confirmation_and_opens_signatories(): void
    {
        Queue::fake([SendQueuedServiceMail::class]);
        $this->configureMail();
        $tour = $this->tour();

        $this->actingAs($this->admin())->post(route('admin.requests.tour.accept', $tour->serviceRequest))
            ->assertRedirect(route('admin.requests.tour.create', ['serviceRequest' => $tour->serviceRequest, 'tab' => 'signatories']));

        $this->assertSame(ServiceRequestStatus::AssignSignatories, $tour->serviceRequest->fresh()->status);
        Queue::assertPushed(SendQueuedServiceMail::class, fn ($job) => $job->recipientEmail === $tour->serviceRequest->client->email
            && str_contains($job->mailable->renderedBody, 'accepted')
            && str_contains($job->mailable->renderedBody, 'October 20, 2026'));
    }

    public function test_accepted_tour_renders_signatory_options(): void
    {
        $tour = $this->tour(ServiceRequestStatus::AssignSignatories);

        $this->actingAs($this->admin())->get(route('admin.requests.tour.create', $tour->serviceRequest))
            ->assertInertia(fn (Assert $page) => $page->component('admin/tour-request-form')
                ->where('serviceRequest.status_value', 'assign-signatories')
                ->where('signatoryOptions.prepared_by.0', 'Richelle Jem Jobog'));
    }

    public function test_cancellation_saves_reason_and_queues_email(): void
    {
        Queue::fake([SendQueuedServiceMail::class]);
        $this->configureMail();
        $tour = $this->tour();

        $this->actingAs($this->admin())->post(route('admin.requests.tour.cancel', $tour->serviceRequest), ['reason' => 'Facility unavailable.'])
            ->assertRedirect(route('admin.requests.index'));

        $this->assertSame(ServiceRequestStatus::Cancelled, $tour->serviceRequest->fresh()->status);
        $this->assertDatabaseHas('service_request_logs', ['service_request_id' => $tour->service_request_id, 'description' => 'Tour cancelled. Reason: Facility unavailable.']);
        Queue::assertPushed(SendQueuedServiceMail::class, fn ($job) => str_contains($job->mailable->renderedBody, 'Facility unavailable.'));
    }

    public function test_rescheduling_updates_both_schedules_and_emails_the_reason(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->setTime(9, 0));
        Queue::fake([SendQueuedServiceMail::class]);
        $this->configureMail();
        $tour = $this->tour();
        $tour->serviceRequest->update(['is_appointment' => true, 'is_appointment_approved' => true, 'appointment_date' => '2026-10-20', 'appointment_time' => '09:00']);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.reschedule', $tour->serviceRequest), [
            'visit_date' => '2026-10-22', 'visit_time' => '14:00', 'reason' => 'Facility maintenance.',
        ])->assertRedirect();

        $this->assertSame('2026-10-22', $tour->fresh()->visit_date->toDateString());
        $this->assertSame('14:00:00', $tour->fresh()->visit_time);
        $this->assertSame('2026-10-22', $tour->serviceRequest->fresh()->appointment_date->toDateString());
        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
        Queue::assertPushed(SendQueuedServiceMail::class, fn ($job) => str_contains($job->mailable->renderedBody, 'October 22, 2026')
            && str_contains($job->mailable->renderedBody, 'October 20, 2026')
            && str_contains($job->mailable->renderedBody, 'Facility maintenance.'));
    }

    #[TestWith(['cancel', [], ['reason']])]
    #[TestWith(['reschedule', [], ['reason', 'visit_date', 'visit_time']])]
    #[TestWith(['reschedule', ['reason' => 'Maintenance', 'visit_date' => '2026-09-16', 'visit_time' => '09:00'], ['visit_date']])]
    #[TestWith(['reschedule', ['reason' => 'Maintenance', 'visit_date' => '2026-09-17', 'visit_time' => '08:00'], ['visit_time']])]
    public function test_invalid_actions_do_not_change_the_tour_or_send_mail(string $action, array $data, array $errors): void
    {
        $this->travelTo(now()->setDate(2026, 9, 17)->setTime(9, 0));
        Queue::fake();
        $tour = $this->tour();

        $this->actingAs($this->admin())->post(route('admin.requests.tour.'.$action, $tour->serviceRequest), $data)->assertSessionHasErrors($errors);

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
        $this->assertSame('2026-10-20', $tour->fresh()->visit_date->toDateString());
        Queue::assertNothingPushed();
    }

    public function test_signatories_are_saved_without_payment_verification(): void
    {
        $tour = $this->tour(ServiceRequestStatus::AssignSignatories);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.signatories', $tour->serviceRequest), $this->signatories())
            ->assertRedirect(route('admin.requests.tour.create', ['serviceRequest' => $tour->serviceRequest, 'tab' => 'for-signature']));

        $this->assertSame(ServiceRequestStatus::ForSignature, $tour->serviceRequest->fresh()->status);
        $this->assertDatabaseHas('tour_requests', ['id' => $tour->id, ...$this->signatories()]);
    }

    public function test_tour_request_pdf_is_available_with_encoded_request_data(): void
    {
        $tour = $this->tour();
        $tour->items()->create(['testing_lab' => ['physical'], 'pilot_plant' => ['spinning'], 'others' => ['tela-gallery']]);

        $this->actingAs($this->admin())->get(route('admin.requests.tour.request.pdf', $tour->serviceRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"plant-tour-request-{$tour->service_request_id}.pdf\"");
    }

    public function test_for_signature_tour_has_a_confirmation_pdf(): void
    {
        $tour = $this->tour(ServiceRequestStatus::ForSignature);
        $tour->items()->create(['testing_lab' => ['physical'], 'pilot_plant' => ['spinning'], 'others' => ['tela-gallery']]);
        $tour->update($this->signatories());

        $this->actingAs($this->admin())->get(route('admin.requests.tour.confirmation.pdf', $tour->serviceRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', "attachment; filename=\"plant-tour-confirmation-{$tour->service_request_id}.pdf\"");
    }

    public function test_signatories_require_the_four_primary_names(): void
    {
        $tour = $this->tour(ServiceRequestStatus::AssignSignatories);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.signatories', $tour->serviceRequest), [])
            ->assertSessionHasErrors(['prepared_by', 'noted_by', 'conforme_primary', 'conforme_secondary']);

        $this->assertNull($tour->fresh()->prepared_by);
        $this->assertSame(ServiceRequestStatus::AssignSignatories, $tour->serviceRequest->fresh()->status);
    }

    public function test_custom_signatory_names_are_saved(): void
    {
        $tour = $this->tour(ServiceRequestStatus::AssignSignatories);
        $names = [
            'prepared_by' => 'Alex Santos',
            'noted_by' => 'Maria Cruz',
            'conforme_primary' => 'Dr. José Reyes',
            'conforme_secondary' => 'Ms. Ana Lim',
            'conforme_optional' => 'Engr. Ben Garcia',
            'remarks' => null,
        ];

        $this->actingAs($this->admin())->post(route('admin.requests.tour.signatories', $tour->serviceRequest), $names)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tour_requests', ['id' => $tour->id, ...$names]);
        $this->assertSame(ServiceRequestStatus::ForSignature, $tour->serviceRequest->fresh()->status);
    }

    public function test_signatories_cannot_be_submitted_before_acceptance(): void
    {
        $tour = $this->tour();

        $this->actingAs($this->admin())->post(route('admin.requests.tour.signatories', $tour->serviceRequest), $this->signatories())->assertUnprocessable();

        $this->assertNull($tour->fresh()->prepared_by);
        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
    }

    #[TestWith(['accept'])]
    #[TestWith(['cancel'])]
    public function test_accepted_tours_cannot_repeat_pending_actions(string $action): void
    {
        Queue::fake();
        $tour = $this->tour(ServiceRequestStatus::AssignSignatories);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.'.$action, $tour->serviceRequest), ['reason' => 'Cancelled'])->assertUnprocessable();

        $this->assertSame(ServiceRequestStatus::AssignSignatories, $tour->serviceRequest->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_unassigned_admin_cannot_accept_tour(): void
    {
        $tour = $this->tour();
        $admin = $this->admin();
        $admin->services()->delete();

        $this->actingAs($admin)->post(route('admin.requests.tour.accept', $tour->serviceRequest))->assertForbidden();

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
    }

    public function test_non_admin_cannot_accept_tour(): void
    {
        $tour = $this->tour();
        $admin = $this->admin();
        $admin->syncRoles([Role::findOrCreate('staff')]);

        $this->actingAs($admin)->post(route('admin.requests.tour.accept', $tour->serviceRequest))->assertForbidden();

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
    }

    public function test_guest_cannot_accept_tour(): void
    {
        $tour = $this->tour();

        $this->post(route('admin.requests.tour.accept', $tour->serviceRequest))->assertRedirect(route('admin.login'));

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
    }

    public function test_missing_smtp_reports_email_failure_without_claiming_delivery(): void
    {
        $this->seed(TourNotificationTemplateSeeder::class);
        Queue::fake();
        $tour = $this->tour();

        $this->actingAs($this->admin())->post(route('admin.requests.tour.accept', $tour->serviceRequest))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'email could not be queued'));

        $this->assertSame(ServiceRequestStatus::AssignSignatories, $tour->serviceRequest->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_non_tour_service_cannot_be_accepted_as_a_tour(): void
    {
        Queue::fake();
        $tour = $this->tour();
        $tour->serviceRequest->update(['service' => ClientService::RddServices]);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.accept', $tour->serviceRequest))->assertUnprocessable();

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_unconfirmed_appointment_cannot_be_accepted(): void
    {
        Queue::fake();
        $tour = $this->tour();
        $tour->serviceRequest->update(['is_appointment' => true, 'is_appointment_approved' => false]);

        $this->actingAs($this->admin())->post(route('admin.requests.tour.accept', $tour->serviceRequest))->assertUnprocessable();

        $this->assertSame(ServiceRequestStatus::Pending, $tour->serviceRequest->fresh()->status);
        Queue::assertNothingPushed();
    }

    private function tour(ServiceRequestStatus $status = ServiceRequestStatus::Pending): TourRequest
    {
        return TourRequest::factory()->for(ServiceRequest::factory()->create([
            'service' => ClientService::PlantTourServices, 'status' => $status, 'is_appointment' => false,
        ]))->create(['visit_date' => '2026-10-20', 'visit_time' => '09:00']);
    }

    private function admin(): User
    {
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions([Permission::findOrCreate('requests.read'), Permission::findOrCreate('requests.write')]);
        $user = User::factory()->create();
        $user->assignRole($role);
        $user->services()->create(['service' => ClientService::PlantTourServices]);

        return $user;
    }

    private function configureMail(): void
    {
        $this->seed(TourNotificationTemplateSeeder::class);
        SmtpSetting::query()->create(['host' => 'smtp.example.test', 'port' => '587', 'username' => 'test', 'password' => 'test', 'from_address' => 'test@example.test', 'from_name' => 'SRRIS']);
    }

    /** @return array<string, string|null> */
    private function signatories(): array
    {
        return ['prepared_by' => 'Richelle Jem Jobog', 'noted_by' => 'Zailla Payag', 'conforme_primary' => 'Ms. Evangeline Flor P. Manalang', 'conforme_secondary' => 'Ms. Merlita I. Odi', 'conforme_optional' => null, 'remarks' => 'Bring identification.'];
    }
}
