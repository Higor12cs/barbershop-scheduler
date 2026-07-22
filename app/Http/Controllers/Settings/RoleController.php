<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $usersCount = $this->usersCountByRole();

        $roles = Role::query()
            ->where('tenant_id', tenant('id'))
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'is_admin' => $role->name === Permissions::ROLE_ADMIN,
                'permissions_count' => $role->permissions->count(),
                'users_count' => (int) ($usersCount[$role->id] ?? 0),
            ])
            ->all();

        return Inertia::render('Settings/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Settings/Roles/Create', [
            'groups' => Permissions::grouped(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        Role::findOrCreate($data['name'], Permissions::GUARD)->syncPermissions($data['permissions'] ?? []);

        return to_route('settings.roles.index')->with('success', 'Papel criado com sucesso!');
    }

    public function edit(int $role): Response
    {
        $model = $this->findRole($role);

        if ($model->name === Permissions::ROLE_ADMIN) {
            abort(404);
        }

        return Inertia::render('Settings/Roles/Edit', [
            'role' => [
                'id' => $model->id,
                'name' => $model->name,
                'permissions' => $model->permissions->pluck('name')->all(),
            ],
            'groups' => Permissions::grouped(),
        ]);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $model = $this->findRole($role);

        if ($model->name === Permissions::ROLE_ADMIN) {
            return back()->with('error', 'O papel Administrador não pode ser alterado.');
        }

        $data = $this->validateRole($request, $model->id);

        $model->update(['name' => $data['name']]);
        $model->syncPermissions($data['permissions'] ?? []);

        return to_route('settings.roles.index')->with('success', 'Papel atualizado com sucesso!');
    }

    public function destroy(int $role): RedirectResponse
    {
        $model = $this->findRole($role);

        if ($model->name === Permissions::ROLE_ADMIN) {
            return back()->with('error', 'O papel Administrador não pode ser removido.');
        }

        if (($this->usersCountByRole()[$model->id] ?? 0) > 0) {
            return back()->with('error', 'Este papel possui usuários vinculados e não pode ser removido.');
        }

        $model->delete();

        return back()->with('success', 'Papel removido com sucesso!');
    }

    private function validateRole(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:50',
                Rule::unique($this->rolesTable(), 'name')->where('tenant_id', tenant('id'))->ignore($ignoreId),
            ],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ], [], [
            'name' => 'nome',
            'permissions' => 'permissões',
        ]);
    }

    private function usersCountByRole(): array
    {
        return DB::connection((new Role)->getConnectionName())
            ->table('model_has_roles')
            ->where('tenant_id', tenant('id'))
            ->selectRaw('role_id, count(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id')
            ->all();
    }

    private function rolesTable(): string
    {
        return (new Role)->getConnectionName().'.roles';
    }

    private function findRole(int $role): Role
    {
        return Role::query()->where('tenant_id', tenant('id'))->findOrFail($role);
    }
}
