<?php

namespace Database\Seeders;

use App\Http\Controllers\Admin\AdminManagementController;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(AdminManagementController::MODULES)
            ->flatMap(fn (string $module) => in_array($module, AdminManagementController::READ_ONLY_MODULES, true)
                ? ["{$module}.read"]
                : ["{$module}.read", "{$module}.write"])
            ->map(fn (string $permission) => Permission::findOrCreate($permission));
        $role = Role::findOrCreate('superadmin');
        $role->syncPermissions($permissions);

        $user = User::query()->updateOrCreate(
            ['username' => '826'],
            [
                'name' => 'Super Administrator',
                'email' => 'iamcarlllemos@gmail.com',
                'account_status' => 'active',
                'role_type' => 'superadmin',
                'password' => 'password@ptri',
            ],
        );

        $user->syncRoles([$role]);
    }
}
