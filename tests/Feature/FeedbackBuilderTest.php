<?php

namespace Tests\Feature;

use App\Models\FeedbackDimension;
use App\Models\FeedbackItem;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FeedbackBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_feedback_builder_index(): void
    {
        $dimension = FeedbackDimension::factory()->create(['name' => 'Reliability']);
        FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'description' => 'Provided what was requested']);

        $response = $this->actingAs($this->adminUser())->get('/admin/feedback-builder');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback-builder')
            ->has('dimensions', 1)
            ->where('dimensions.0.name', 'Reliability')
            ->has('dimensions.0.items', 1),
        );
    }

    public function test_admin_can_visualize_the_customer_satisfaction_feedback_form(): void
    {
        $dimension = FeedbackDimension::factory()->create(['name' => 'Reliability']);
        FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'description' => 'Provided what was requested']);
        FeedbackRating::factory()->create(['name' => 'Excellent', 'value' => '5']);

        $response = $this->actingAs($this->adminUser())->get('/admin/feedback-builder/visualize');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback-visualization')
            ->where('dimensions.0.name', 'Reliability')
            ->where('ratings.0.value', '5'),
        );
    }

    public function test_admin_can_create_a_dimension(): void
    {
        $response = $this->actingAs($this->adminUser())->post('/admin/feedback-builder', ['name' => 'Assurance']);

        $response->assertRedirect('/admin/feedback-builder');
        $this->assertDatabaseHas('feedback_dimensions', ['name' => 'Assurance']);
    }

    public function test_creating_a_dimension_requires_a_name(): void
    {
        $response = $this->actingAs($this->adminUser())->post('/admin/feedback-builder', ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_dimension(): void
    {
        $dimension = FeedbackDimension::factory()->create(['name' => 'Old name']);

        $response = $this->actingAs($this->adminUser())->put("/admin/feedback-builder/{$dimension->id}", ['name' => 'New name']);

        $response->assertRedirect('/admin/feedback-builder');
        $this->assertSame('New name', $dimension->fresh()->name);
    }

    public function test_admin_can_reorder_dimensions(): void
    {
        $first = FeedbackDimension::factory()->create(['position' => 1]);
        $second = FeedbackDimension::factory()->create(['position' => 2]);

        $response = $this->actingAs($this->adminUser())->put('/admin/feedback-builder/order', [
            'dimension_ids' => [$second->id, $first->id],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_admin_can_delete_a_dimension_with_the_confirmation_code(): void
    {
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->delete("/admin/feedback-builder/{$dimension->id}", ['delete_code' => '1234']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('feedback_dimensions', ['id' => $dimension->id]);
        $this->assertDatabaseMissing('feedback_items', ['id' => $item->id]);
    }

    public function test_deleting_a_dimension_requires_the_correct_confirmation_code(): void
    {
        $dimension = FeedbackDimension::factory()->create();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->delete("/admin/feedback-builder/{$dimension->id}", ['delete_code' => '0000']);

        $response->assertSessionHasErrors('delete_code');
        $this->assertDatabaseHas('feedback_dimensions', ['id' => $dimension->id]);
    }

    public function test_admin_can_add_an_item_to_a_dimension(): void
    {
        $dimension = FeedbackDimension::factory()->create();

        $response = $this->actingAs($this->adminUser())->post("/admin/feedback-builder/{$dimension->id}/items", [
            'description' => 'Responded quickly to my needs',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feedback_items', [
            'feedback_dimension_id' => $dimension->id,
            'description' => 'Responded quickly to my needs',
        ]);
    }

    public function test_admin_can_update_an_item(): void
    {
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'description' => 'Old description']);

        $response = $this
            ->actingAs($this->adminUser())
            ->put("/admin/feedback-builder/{$dimension->id}/items/{$item->id}", ['description' => 'New description']);

        $response->assertRedirect();
        $this->assertSame('New description', $item->fresh()->description);
    }

    public function test_admin_can_reorder_descriptions_within_a_dimension(): void
    {
        $dimension = FeedbackDimension::factory()->create();
        $first = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'position' => 1]);
        $second = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id, 'position' => 2]);

        $response = $this->actingAs($this->adminUser())->put("/admin/feedback-builder/{$dimension->id}/items/order", [
            'item_ids' => [$second->id, $first->id],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame(2, $first->fresh()->position);
    }

    public function test_admin_can_delete_an_item_with_the_confirmation_code(): void
    {
        $dimension = FeedbackDimension::factory()->create();
        $item = FeedbackItem::factory()->create(['feedback_dimension_id' => $dimension->id]);

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->delete("/admin/feedback-builder/{$dimension->id}/items/{$item->id}", ['delete_code' => '1234']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('feedback_items', ['id' => $item->id]);
        $this->assertDatabaseHas('feedback_dimensions', ['id' => $dimension->id]);
    }

    public function test_admin_can_view_the_rating_scale_index(): void
    {
        FeedbackRating::factory()->create(['name' => 'Excellent', 'value' => '5']);

        $response = $this->actingAs($this->adminUser())->get('/admin/feedback-builder/ratings');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback-ratings')
            ->has('ratings.data', 1),
        );
    }

    public function test_admin_can_create_a_rating(): void
    {
        $response = $this->actingAs($this->adminUser())->post('/admin/feedback-builder/ratings', [
            'name' => 'Not Applicable',
            'value' => 'N/A',
        ]);

        $response->assertRedirect('/admin/feedback-builder/ratings');
        $this->assertDatabaseHas('feedback_ratings', ['name' => 'Not Applicable', 'value' => 'N/A']);
    }

    public function test_admin_can_update_a_rating(): void
    {
        $rating = FeedbackRating::factory()->create(['name' => 'Good', 'value' => '4']);

        $response = $this->actingAs($this->adminUser())->put("/admin/feedback-builder/ratings/{$rating->id}", [
            'name' => 'Very Good',
            'value' => '4',
        ]);

        $response->assertRedirect('/admin/feedback-builder/ratings');
        $this->assertSame('Very Good', $rating->fresh()->name);
    }

    public function test_admin_can_delete_a_rating_with_the_confirmation_code(): void
    {
        $rating = FeedbackRating::factory()->create();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->delete("/admin/feedback-builder/ratings/{$rating->id}", ['delete_code' => '1234']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('feedback_ratings', ['id' => $rating->id]);
    }

    public function test_admin_can_view_the_questions_index(): void
    {
        FeedbackQuestion::factory()->create(['name' => 'Areas for improvement']);

        $response = $this->actingAs($this->adminUser())->get('/admin/feedback-builder/questions');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/feedback-questions')
            ->has('questions.data', 1),
        );
    }

    public function test_admin_can_create_a_question(): void
    {
        $response = $this->actingAs($this->adminUser())->post('/admin/feedback-builder/questions', [
            'name' => 'Areas for improvement',
        ]);

        $response->assertRedirect('/admin/feedback-builder/questions');
        $this->assertDatabaseHas('feedback_questions', ['name' => 'Areas for improvement']);
    }

    public function test_creating_a_question_requires_a_name(): void
    {
        $response = $this->actingAs($this->adminUser())->post('/admin/feedback-builder/questions', ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_question(): void
    {
        $question = FeedbackQuestion::factory()->create(['name' => 'Old question']);

        $response = $this->actingAs($this->adminUser())->put("/admin/feedback-builder/questions/{$question->id}", [
            'name' => 'New question',
        ]);

        $response->assertRedirect('/admin/feedback-builder/questions');
        $this->assertSame('New question', $question->fresh()->name);
    }

    public function test_admin_can_delete_a_question_with_the_confirmation_code(): void
    {
        $question = FeedbackQuestion::factory()->create();

        $response = $this
            ->actingAs($this->adminUser())
            ->withSession(['admin.delete_challenge' => '1234'])
            ->delete("/admin/feedback-builder/questions/{$question->id}", ['delete_code' => '1234']);

        $response->assertRedirect();
        $this->assertDatabaseMissing('feedback_questions', ['id' => $question->id]);
    }

    private function adminUser(): User
    {
        $permissions = collect(['feedback-builder'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::factory()->create();
        $user->syncRoles([$role]);

        return $user;
    }
}
