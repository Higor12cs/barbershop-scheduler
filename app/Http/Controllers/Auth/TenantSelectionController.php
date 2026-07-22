<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TenantSelectionController extends Controller
{
    public function index(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        $tenants = $user->accessibleTenants()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ])
            ->values();

        if ($tenants->count() === 1 && ! $user->isSuperAdmin()) {
            $tenant = $tenants->first();
            $request->session()->put('tenant_id', $tenant['id']);

            return to_route('dashboard.index');
        }

        return Inertia::render('Auth/TenantSelection', [
            'tenants' => $tenants,
            'isSuperAdmin' => $user->isSuperAdmin(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'uuid'],
        ]);

        $tenant = $request->user()->findAccessibleTenant($validated['tenant_id']);

        if (! $tenant) {
            abort(403, 'Você não tem acesso a este ambiente.');
        }

        $request->session()->put('tenant_id', $tenant->id);

        return to_route('dashboard.index')
            ->with('success', "Ambiente {$tenant->name} selecionado!");
    }
}
