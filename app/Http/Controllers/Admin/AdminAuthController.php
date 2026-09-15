<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('dashboard');
        }

        return Inertia::render('admin/login');
    }

    public function store(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (! Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'account_status' => 'active'])) {
            return back()->withErrors(['username' => 'The supplied credentials are invalid.'])->onlyInput('username');
        }

        $request->session()->regenerate();

        if (! $request->user()?->hasRole('superadmin')) {
            Auth::logout();

            return back()->withErrors(['username' => 'This account does not have admin access.']);
        }

        return to_route('dashboard');
    }

    public function destroy(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return to_route('admin.login');
    }
}
