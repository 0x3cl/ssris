<?php

namespace Tests\Feature;

use App\Enums\ServiceRequestStatus;
use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use App\Models\FeedbackLink;
use App\Models\FeedbackRating;
use App\Models\FeedbackResponse;
use App\Models\RddRequest;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeedbackLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_a_feedback_link(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();

        $response = $this->actingAs($this->adminUser())
            ->post("/admin/requests/{$serviceRequest->id}/rdd-request/feedback/generate-link");

        $response->assertRedirect();
        $this->assertDatabaseCount('feedback_links', 1);

        $link = FeedbackLink::query()->firstOrFail();
        $this->assertSame($serviceRequest->id, $link->service_request_id);
        $this->assertTrue($link->expires_at->between(now()->addHours(23), now()->addHours(25)));
        $this->assertNull($link->submitted_at);
    }

    public function test_admin_can_generate_multiple_links_for_the_same_request(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $admin = $this->adminUser();

        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/rdd-request/feedback/generate-link");
        $this->actingAs($admin)->post("/admin/requests/{$serviceRequest->id}/rdd-request/feedback/generate-link");

        $this->assertDatabaseCount('feedback_links', 2);
        $tokens = FeedbackLink::query()->pluck('token');
        $this->assertNotSame($tokens[0], $tokens[1]);
    }

    public function test_public_can_view_a_valid_feedback_form(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create(['name' => 'Reliability']);
        FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'description' => 'Timeliness']);
        FeedbackRating::factory()->create(['name' => 'Excellent', 'value' => '5']);

        $response = $this->get("/feedback/{$link->token}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('feedback-form')
            ->where('token', $link->token)
            ->where('dimensions.0.name', 'Reliability')
            ->where('ratings.0.value', '5'),
        );
    }

    public function test_expired_link_shows_the_unavailable_page(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->expired()->create(['service_request_id' => $serviceRequest->id]);

        $response = $this->get("/feedback/{$link->token}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('feedback-unavailable')
            ->where('reason', 'expired'),
        );
    }

    public function test_unknown_token_shows_the_unavailable_page(): void
    {
        $response = $this->get('/feedback/does-not-exist');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('feedback-unavailable')
            ->where('reason', 'not-found'),
        );
    }

    public function test_client_can_submit_feedback(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);
        FeedbackRating::factory()->create(['value' => '5']);

        $response = $this->post("/feedback/{$link->token}", [
            'ratings' => [(string) $item->id => '5'],
            'answers' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('feedback_responses', 1);
        $this->assertNotNull($link->fresh()->submitted_at);
        $this->assertSame(ServiceRequestStatus::Completed, $serviceRequest->fresh()->status);
    }

    public function test_admin_can_view_a_submitted_response(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);
        FeedbackRating::factory()->create(['value' => '5']);

        $this->post("/feedback/{$link->token}", ['ratings' => [(string) $item->id => '5']]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/rdd-request/feedback/responses/{$link->id}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback-response')
            ->where('responseRatings.'.$item->id, '5'),
        );
    }

    public function test_admin_can_still_open_the_feedback_tab_after_completion(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);
        FeedbackRating::factory()->create(['value' => '5']);

        $this->post("/feedback/{$link->token}", ['ratings' => [(string) $item->id => '5']]);

        $response = $this->actingAs($this->adminUser())
            ->get("/admin/requests/{$serviceRequest->id}/rdd-request/feedback");

        $response->assertOk();
    }

    public function test_submitted_response_keeps_a_snapshot_immune_to_later_edits(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create(['name' => 'Reliability']);
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'description' => 'Original description']);
        $rating = FeedbackRating::factory()->create(['name' => 'Excellent', 'value' => '5']);

        $this->post("/feedback/{$link->token}", [
            'ratings' => [(string) $item->id => '5'],
        ]);

        $response = FeedbackResponse::query()->firstOrFail();
        $this->assertSame('Reliability', $response->snapshot['dimensions'][0]['name']);
        $this->assertSame('Original description', $response->snapshot['dimensions'][0]['items'][0]['description']);
        $this->assertSame('Excellent', $response->snapshot['ratings'][0]['name']);

        // Editing (or deleting) the live records afterward must not change the frozen snapshot.
        $dimension->update(['name' => 'Renamed dimension']);
        $item->update(['description' => 'Edited description']);
        $rating->delete();

        $response->refresh();
        $this->assertSame('Reliability', $response->snapshot['dimensions'][0]['name']);
        $this->assertSame('Original description', $response->snapshot['dimensions'][0]['items'][0]['description']);
        $this->assertSame('Excellent', $response->snapshot['ratings'][0]['name']);
    }

    public function test_submitting_an_invalid_rating_value_fails_validation(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->create(['service_request_id' => $serviceRequest->id]);
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);
        FeedbackRating::factory()->create(['value' => '5']);

        $response = $this->post("/feedback/{$link->token}", [
            'ratings' => [(string) $item->id => 'not-a-valid-value'],
        ]);

        $response->assertSessionHasErrors('ratings.'.$item->id);
    }

    public function test_a_submitted_link_cannot_be_used_again(): void
    {
        $serviceRequest = $this->awaitingFeedbackRequest();
        $link = FeedbackLink::factory()->submitted()->create(['service_request_id' => $serviceRequest->id]);

        $response = $this->get("/feedback/{$link->token}");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('feedback-unavailable')
            ->where('reason', 'submitted'),
        );
    }

    private function awaitingFeedbackRequest(): ServiceRequest
    {
        $serviceRequest = ServiceRequest::factory()->create(['status' => ServiceRequestStatus::AwaitingFeedback]);
        RddRequest::factory()->create(['service_request_id' => $serviceRequest->id]);

        return $serviceRequest;
    }

    private function adminUser(): User
    {
        $permissions = collect(['requests'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
