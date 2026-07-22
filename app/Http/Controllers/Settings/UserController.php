<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = Tenant::query()->find(tenant('id'))?->users()->orderBy('name')->get() ?? collect();

        return Inertia::render('Settings/Users/Index', [
            'users' => $users->map(fn (User $user): array => [
                'id' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ])->all(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Settings/Users/Create', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function checkEmail(Request $request): JsonResponse
    {
        $email = mb_strtolower(trim((string) $request->string('email')));

        return response()->json([
            'exists' => $email !== '' && User::query()->where('email', $email)->exists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['nullable', 'string', $this->roleRule()],
        ]);

        $user = User::query()->where('email', mb_strtolower($data['email']))->first();
        $existed = (bool) $user;

        if (! $user) {
            if (blank($data['password'])) {
                return back()->withErrors(['password' => 'Senha obrigatória para criar um novo usuário.']);
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],
                'must_change_password' => true,
            ]);
        }

        $user->tenants()->syncWithoutDetaching([tenant('id')]);
        $user->syncRoles(array_filter([$data['role'] ?? null]));

        return to_route('settings.users.index')->with('success', $existed
            ? 'Usuário existente vinculado a este ambiente!'
            : 'Usuário criado com sucesso!');
    }

    public function edit(string $user): Response
    {
        $model = $this->findMember($user);

        return Inertia::render('Settings/Users/Edit', [
            'user' => [
                'id' => $model->uuid,
                'name' => $model->name,
                'email' => $model->email,
                'role' => $model->getRoleNames()->first(),
                'belongs_to_other_tenant' => $model->tenants()->whereKeyNot(tenant('id'))->exists(),
            ],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $model = $this->findMember($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', $this->roleRule()],
        ]);

        if (! $model->tenants()->whereKeyNot(tenant('id'))->exists()) {
            $model->update(['name' => $data['name']]);
        }

        $model->syncRoles(array_filter([$data['role'] ?? null]));

        return to_route('settings.users.index')->with('success', 'Usuário atualizado com sucesso!');
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $model = $this->findMember($user);

        if ($model->uuid === $request->user()?->uuid) {
            return back()->with('error', 'Você não pode remover a si mesmo.');
        }

        $model->syncRoles([]);
        $model->tenants()->detach(tenant('id'));

        return back()->with('success', 'Usuário removido deste ambiente!');
    }

    private function findMember(string $uuid): User
    {
        $user = User::query()->where('uuid', $uuid)->firstOrFail();

        abort_unless($user->tenants()->whereKey(tenant('id'))->exists(), 404);

        return $user;
    }

    private function roleOptions(): array
    {
        return Role::query()
            ->where('tenant_id', tenant('id'))
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
            ->all();
    }

    private function roleRule(): Exists
    {
        return Rule::exists((new Role)->getConnectionName().'.roles', 'name')
            ->where('tenant_id', tenant('id'));
    }
}
