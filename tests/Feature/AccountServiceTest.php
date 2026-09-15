<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_stores_account_fields_and_hashes_password(): void
    {
        $user = (new AccountService)->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'secret-password',
            'account_type' => 'staff',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'account_type' => 'staff', 'account_status' => 'active']);
        $this->assertTrue(Hash::check('secret-password', $user->fresh()->password));
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_update_preserves_blank_password_and_resets_verification_when_email_changes(): void
    {
        $user = User::factory()->create(['account_type' => 'staff', 'account_status' => 'active']);
        $password = $user->password;

        (new AccountService)->update($user->id, ['email' => 'changed@example.com', 'password' => '']);

        $this->assertSame($password, $user->fresh()->password);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'changed@example.com']);
    }

    public function test_update_hashes_a_replacement_password_and_accepts_unchanged_email(): void
    {
        $user = User::factory()->create(['account_type' => 'staff', 'account_status' => 'active']);

        (new AccountService)->update($user->id, ['email' => $user->email, 'password' => 'replacement-password']);

        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_duplicate_email_is_rejected_without_changing_user(): void
    {
        User::factory()->create(['email' => 'taken@example.com', 'account_type' => 'staff', 'account_status' => 'active']);
        $user = User::factory()->create(['account_type' => 'staff', 'account_status' => 'active']);

        try {
            (new AccountService)->update($user->id, ['email' => 'taken@example.com']);
            $this->fail('Expected email validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('email', $exception->errors());
            $this->assertSame($user->email, $user->fresh()->email);
        }
    }

    public function test_users_can_be_paginated_found_and_deleted(): void
    {
        $users = User::factory()->count(2)->create(['account_type' => 'staff', 'account_status' => 'active']);
        $service = new AccountService;

        $page = $service->paginate(1);

        $this->assertSame(2, $page->total());
        $this->assertCount(1, $page->items());
        $this->assertTrue($users->last()->is($page->items()[0]));
        $this->assertTrue($users->first()->is($service->find($users->first()->id)));
        $this->assertTrue($service->delete($users->first()->id));
        $this->assertModelMissing($users->first());
    }

    public function test_missing_user_throws_model_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        (new AccountService)->find(999);
    }

    public function test_creation_requires_account_fields_and_valid_credentials(): void
    {
        try {
            (new AccountService)->create(['email' => 'invalid', 'password' => 'short']);
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $exception) {
            foreach (['name', 'email', 'password', 'account_type', 'account_status'] as $field) {
                $this->assertArrayHasKey($field, $exception->errors());
            }
            $this->assertDatabaseCount('users', 0);
        }
    }
}
