<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminModuleController extends Controller
{
    public function show(string $module = 'dashboard'): Response
    {
        $modules = [
            'dashboard' => 'Dashboard',
            'reports' => 'Reports',
            'users' => 'Users',
            'roles-and-permissions' => 'Roles and Permissions',
            'smtp-configuration' => 'SMTP Configuration',
        ];

        abort_unless(array_key_exists($module, $modules), 404);

        return Inertia::render('admin/module', [
            'module' => $module,
            'title' => $modules[$module],
        ]);
    }
}
