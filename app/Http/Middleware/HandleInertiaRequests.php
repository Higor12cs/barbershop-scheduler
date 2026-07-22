<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => fn (): ?array => $request->user()
                    ? [
                        'id' => $request->user()->uuid,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'is_super_admin' => (bool) $request->user()->is_super_admin,
                        'must_change_password' => (bool) $request->user()->must_change_password,
                    ]
                    : null,
                'permissions' => fn (): array => ($request->user() && tenant())
                    ? ($request->user()->isSuperAdmin()
                        ? Permissions::all()
                        : $request->user()->getAllPermissions()->pluck('name')->all())
                    : [],
            ],
            'tenant' => fn (): ?array => tenant()
                ? [
                    'id' => tenant('id'),
                    'name' => tenant('name'),
                    'slug' => tenant('slug'),
                    'modules' => tenant()->enabledModules(),
                ]
                : null,
            'tenants' => fn (): array => $request->user()
                ? $request->user()->accessibleTenants()
                    ->map(fn (Tenant $tenant): array => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                    ])
                    ->all()
                : [],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success'),
                'error' => fn (): ?string => $request->session()->get('error'),
                'warning' => fn (): ?string => $request->session()->get('warning'),
                'info' => fn (): ?string => $request->session()->get('info'),
                'created_appointment' => fn (): ?array => $request->session()->get('created_appointment'),
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
