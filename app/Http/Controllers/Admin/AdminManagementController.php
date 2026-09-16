<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ClientService;
use App\Enums\FormTemplateKey;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Mail\FormTemplateMail;
use App\Models\FormTemplate;
use App\Models\ServiceRequest;
use App\Models\SmtpSetting;
use App\Models\UlimsSetting;
use App\Models\User;
use App\Services\SmtpMailerConfigurator;
use App\Services\UlimsClient;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class AdminManagementController extends Controller
{
    public const MODULES = ['dashboard', 'clients', 'requests', 'site-visitors', 'reports', 'users', 'roles-and-permissions', 'form-templates', 'feedback-builder', 'smtp-configuration', 'ulims-configuration', 'audit-trails'];

    public const READ_ONLY_MODULES = ['audit-trails', 'reports'];

    public function dashboard(Request $request): Response
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $selectedMonth = CarbonImmutable::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $monthlyRequests = ServiceRequest::query()->whereBetween('created_at', [$selectedMonth, $selectedMonth->endOfMonth()]);
        $statuses = ServiceRequestStatus::cases();
        $statusTotals = (clone $monthlyRequests)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('admin/dashboard', [
            'filters' => ['month' => $selectedMonth->format('Y-m')],
            'stats' => [
                'total' => (clone $monthlyRequests)->count(),
                'walkIns' => (clone $monthlyRequests)->where('is_appointment', false)->count(),
                'appointments' => (clone $monthlyRequests)->where('is_appointment', true)->count(),
                'pending' => $statusTotals->get('pending', 0),
            ],
            'series' => (clone $monthlyRequests)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
            'statusBreakdown' => collect($statuses)->map(fn (ServiceRequestStatus $status): array => [
                'label' => $status->label(),
                'total' => $statusTotals->get($status->value, 0),
            ]),
            'serviceBreakdown' => (clone $monthlyRequests)
                ->selectRaw('service, COUNT(*) as total')
                ->groupBy('service')
                ->orderByDesc('total')
                ->get()
                ->map(fn ($item): array => [
                    'label' => $item->service instanceof ClientService
                        ? $item->service->label()
                        : (ClientService::tryFrom($item->service)?->label() ?? $item->service),
                    'total' => $item->total,
                ]),
        ]);
    }

    public function roles(Request $request): Response
    {
        $entries = $this->entries($request);
        $search = trim($request->string('search')->value());

        return Inertia::render('admin/roles', [
            'filters' => compact('entries', 'search'),
            'roles' => Role::query()
                ->withCount('users')
                ->with('permissions')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate($entries)
                ->withQueryString(),
        ]);
    }

    public function createRole(): Response
    {
        return Inertia::render('admin/role-form', ['modules' => self::MODULES, 'readOnlyModules' => self::READ_ONLY_MODULES, 'role' => null]);
    }

    public function editRole(Role $role): Response
    {
        return Inertia::render('admin/role-form', ['modules' => self::MODULES, 'readOnlyModules' => self::READ_ONLY_MODULES, 'role' => $role->load('permissions')]);
    }

    public function saveRole(Request $request, ?Role $role = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => [Rule::in(['read', 'write'])],
        ]);

        if ($role?->name === 'superadmin') {
            $data['name'] = 'superadmin';
        }

        $role ??= Role::create(['name' => $data['name']]);
        $role->update(['name' => $data['name']]);
        $permissions = collect($data['permissions'] ?? [])
            ->only(self::MODULES)
            ->flatMap(function ($access, $module) {
                $access = collect($access)->filter();

                if (in_array($module, self::READ_ONLY_MODULES, true)) {
                    $access = $access->intersect(['read']);
                } elseif ($access->contains('write') && ! $access->contains('read')) {
                    $access->push('read');
                }

                return $access->map(fn ($action) => "{$module}.{$action}");
            });
        $role->syncPermissions($permissions->map(fn ($name) => Permission::findOrCreate($name)));

        return to_route('admin.roles')->with('success', 'Role permissions updated.');
    }

    public function deleteRole(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'superadmin', 422, 'The superadmin role cannot be deleted.');
        $this->validateDeleteChallenge($request);
        $role->delete();

        return back()->with('success', 'Role deleted.');
    }

    public function users(Request $request): Response
    {
        $entries = $this->entries($request);
        $search = trim($request->string('search')->value());
        $role = trim($request->string('role')->value());
        $status = trim($request->string('status')->value());

        return Inertia::render('admin/users', [
            'filters' => compact('entries', 'role', 'search', 'status'),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()
                ->with('roles:id,name')
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($userQuery) use ($search): void {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role)))
                ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('account_status', $status))
                ->latest()
                ->paginate($entries)
                ->withQueryString(),
        ]);
    }

    public function createUser(): Response
    {
        return Inertia::render('admin/user-form', ['roles' => $this->rolesForSelection(), 'serviceOptions' => $this->serviceOptions(), 'user' => null]);
    }

    public function editUser(User $user): Response
    {
        $user->load('roles:id,name', 'services:id,user_id,service');

        return Inertia::render('admin/user-form', [
            'roles' => $this->rolesForSelection(),
            'serviceOptions' => $this->serviceOptions(),
            'user' => [
                ...$user->toArray(),
                'services' => $user->services->pluck('service')->map(fn (ClientService $service) => $service->value),
            ],
        ]);
    }

    public function saveUser(Request $request, ?User $user = null): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user)], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)], 'account_status' => ['required', Rule::in(['active', 'inactive'])], 'role' => ['required', 'exists:roles,name'], 'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'], 'profile_image' => ['nullable', 'image', 'max:2048'], 'services' => ['array'], 'services.*' => [Rule::enum(ClientService::class)]]);
        $user ??= new User;
        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $services = $data['services'] ?? [];
        unset($data['services']);
        $data['role_type'] = $data['role'];
        $user->fill($data)->save();
        $user->syncRoles([$data['role']]);
        $user->services()->delete();
        $user->services()->createMany(array_map(fn (string $service): array => ['service' => $service], $services));

        return to_route('admin.users')->with('success', 'User saved.');
    }

    public function deleteUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account.');
        $this->validateDeleteChallenge($request);
        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    public function smtp(): Response
    {
        return Inertia::render('admin/smtp', ['setting' => SmtpSetting::query()->first()]);
    }

    public function saveSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['required', 'string'],
            'port' => ['required', 'string'],
            'username' => ['required', 'string'],
            'password' => ['nullable', 'string'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string'],
        ]);
        $setting = SmtpSetting::query()->first() ?? new SmtpSetting;
        if ($data['password'] === null || $data['password'] === '') {
            unset($data['password']);
        }
        $setting->fill($data)->save();

        return back()->with('success', 'SMTP configuration updated.');
    }

    public function testSmtp(Request $request): RedirectResponse
    {
        $setting = SmtpSetting::query()->first();

        if ($setting === null) {
            return back()->with('error', 'Save SMTP settings before testing the connection.');
        }

        $template = FormTemplate::query()->where('key', FormTemplateKey::TestNotification)->first();

        if ($template === null) {
            return back()->with('error', 'The "Test Notification" email template is not configured.');
        }

        $recipient = $request->user()->email;
        $rendered = $template->render([
            'name' => $request->user()->name,
            'sent_at' => now()->format('F j, Y g:i A'),
        ]);

        try {
            app(SmtpMailerConfigurator::class)->configure($setting);
            Mail::mailer('smtp')->to($recipient)->send(new FormTemplateMail($rendered['subject'], $rendered['body']));
        } catch (Throwable $exception) {
            return back()->with('error', "SMTP connection test failed: {$exception->getMessage()}");
        }

        return back()->with('success', "Test email sent to {$recipient}. Check your inbox to confirm the SMTP connection is working.");
    }

    public function ulimsSettings(): Response
    {
        return Inertia::render('admin/ulims-settings', ['setting' => UlimsSetting::query()->first()]);
    }

    public function saveUlimsSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'base_url' => ['required', 'url'],
            'username' => ['required', 'string'],
        ]);
        $data['base_url'] = rtrim($data['base_url'], '/');

        $setting = UlimsSetting::query()->first() ?? new UlimsSetting;
        $setting->fill($data)->save();

        return back()->with('success', 'ULIMS configuration updated.');
    }

    public function testUlimsConnection(UlimsClient $ulims): RedirectResponse
    {
        $result = $ulims->testConnection();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function account(Request $request): Response
    {
        return Inertia::render('admin/account', ['user' => $request->user()->only(['name', 'username', 'email', 'profile_image'])]);
    }

    public function saveAccount(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['name' => ['required', 'string'], 'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user)], 'password' => ['nullable', 'string', 'min:8'], 'profile_image' => ['nullable', 'image', 'max:2048']]);
        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->fill($data)->save();

        return back()->with('success', 'Your account has been updated.');
    }

    public function deleteChallenge(Request $request): JsonResponse
    {
        $challenge = (string) random_int(1000, 9999);
        $request->session()->put('admin.delete_challenge', $challenge);

        return response()->json(['challenge' => $challenge]);
    }

    private function entries(Request $request): int
    {
        $entries = (int) $request->integer('entries', 10);

        return in_array($entries, [10, 25, 50], true) ? $entries : 10;
    }

    /**
     * @return Collection<int, Role>
     */
    private function rolesForSelection(): Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    /** @return array<int, array{value: string, label: string}> */
    private function serviceOptions(): array
    {
        return array_map(
            fn (ClientService $service): array => ['value' => $service->value, 'label' => $service->label()],
            ClientService::cases(),
        );
    }

    private function validateDeleteChallenge(Request $request): void
    {
        $data = $request->validate(['delete_code' => ['required', 'digits:4']]);
        $challenge = $request->session()->get('admin.delete_challenge');

        if (! is_string($challenge) || ! hash_equals($challenge, $data['delete_code'])) {
            throw ValidationException::withMessages(['delete_code' => 'Enter the displayed four-digit confirmation code to delete this record.']);
        }

        $request->session()->forget('admin.delete_challenge');
    }
}
