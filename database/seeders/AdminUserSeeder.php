<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(['dashboard', 'requests', 'reports', 'users', 'roles-and-permissions', 'smtp-configuration'])
            ->flatMap(fn (string $module) => ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::query()->updateOrCreate(
            ['username' => '826'],
            [
                'name' => 'Super Administrator',
                'email' => 'superadmin@ptri.local',
                'account_status' => 'active',
                'role_type' => 'superadmin',
                'password' => 'password@ptri',
            ],
        );

        $user->syncRoles([$role]);
    }
}
