<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountService
{
    /** @return LengthAwarePaginator<int, User> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()->orderByDesc('id')->paginate(max(1, min($perPage, 100)));
    }

    public function find(int $id): User
    {
        return User::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): User
    {
        $user = new User;
        $user->forceFill($this->validate($data));
        $user->save();

        return $user;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): User
    {
        $user = $this->find($id);

        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }

        $user->forceFill($this->validate($data, $user));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }

    public function delete(int $id): bool
    {
        return (bool) $this->find($id)->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?User $user = null): array
    {
        $presence = $user ? 'sometimes' : 'required';

        return Validator::make($data, [
            'name' => [$presence, 'required', 'string', 'max:255'],
            'email' => [$presence, 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$presence, 'required', 'string', 'min:8', 'max:72'],
            'account_status' => [$presence, 'required', 'string', 'max:255'],
        ])->validate();
    }
}
