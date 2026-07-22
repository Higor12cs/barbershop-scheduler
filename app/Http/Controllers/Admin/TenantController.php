<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WhatsAppProvider;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Modules;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'active' => $tenant->isActive(),
                'expired' => $tenant->accessExpired(),
                'access_until' => $tenant->access_until?->toDateString(),
                'users_count' => $tenant->users_count,
            ])
            ->all();

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Tenants/Create', [
            'moduleOptions' => Modules::options(),
            'defaultModules' => Modules::defaults(),
            'providerOptions' => WhatsAppProvider::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'active' => ['boolean'],
            'access_until' => ['nullable', 'date'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(Modules::all())],
            'whatsapp_provider' => ['nullable', Rule::enum(WhatsAppProvider::class)],
            'whatsapp_config' => ['nullable', 'array'],
            'whatsapp_config.base_url' => ['nullable', 'string', 'max:255'],
            'whatsapp_config.token' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'string', 'min:8'],
        ]);

        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'active' => $data['active'] ?? true,
            'access_until' => $data['access_until'] ?? null,
            'modules' => array_values($data['modules'] ?? Modules::defaults()),
            'whatsapp_provider' => $data['whatsapp_provider'] ?? null,
            'whatsapp_config' => ($data['whatsapp_provider'] ?? null) ? ($data['whatsapp_config'] ?? []) : null,
        ]);

        $user = User::create([
            'name' => $data['admin_name'],
            'email' => mb_strtolower($data['admin_email']),
            'password' => $data['admin_password'],
            'must_change_password' => true,
        ]);

        $user->tenants()->syncWithoutDetaching([$tenant->id]);

        $tenant->run(function () use ($user): void {
            $user->assignRole(Permissions::ROLE_ADMIN);
        });

        return to_route('admin.tenants.index')->with('success', 'Ambiente criado com sucesso!');
    }

    public function edit(Tenant $tenant): Response
    {
        return Inertia::render('Admin/Tenants/Edit', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'active' => $tenant->isActive(),
                'access_until' => $tenant->access_until?->toDateString(),
                'modules' => $tenant->enabledModules(),
                'whatsapp_provider' => $tenant->whatsapp_provider,
                'whatsapp_config' => $tenant->whatsapp_config ?? [],
                'webhook_url' => route('whatsapp.webhook', ['tenant' => $tenant->id, 'secret' => $tenant->webhook_secret]),
            ],
            'moduleOptions' => Modules::options(),
            'providerOptions' => WhatsAppProvider::options(),
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tenants', 'name')->ignore($tenant->id)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')->ignore($tenant->id)],
            'active' => ['boolean'],
            'access_until' => ['nullable', 'date'],
            'modules' => ['array'],
            'modules.*' => [Rule::in(Modules::all())],
            'whatsapp_provider' => ['nullable', Rule::enum(WhatsAppProvider::class)],
            'whatsapp_config' => ['nullable', 'array'],
            'whatsapp_config.base_url' => ['nullable', 'string', 'max:255'],
            'whatsapp_config.token' => ['nullable', 'string', 'max:255'],
        ]);

        $tenant->name = $data['name'];
        $tenant->slug = $data['slug'];
        $tenant->active = $data['active'] ?? true;
        $tenant->access_until = $data['access_until'] ?? null;
        $tenant->modules = array_values($data['modules'] ?? []);
        $tenant->whatsapp_provider = $data['whatsapp_provider'] ?? null;
        $tenant->whatsapp_config = ($data['whatsapp_provider'] ?? null) ? ($data['whatsapp_config'] ?? []) : null;
        $tenant->save();

        return to_route('admin.tenants.index')->with('success', 'Ambiente atualizado com sucesso!');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return to_route('admin.tenants.index')->with('success', 'Ambiente removido com sucesso!');
    }
}
