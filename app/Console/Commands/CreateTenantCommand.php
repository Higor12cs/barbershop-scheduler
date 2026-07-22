<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Support\Modules;
use App\Support\Permissions;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

#[Signature('app:create-tenant {name} {slug?} {--admin-name=Administrador} {--admin-email=} {--admin-password=}')]
class CreateTenantCommand extends Command
{
    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $slug = trim((string) ($this->argument('slug') ?: Str::slug($name)));

        if ($name === '') {
            $this->error('Tenant name is required.');

            return self::FAILURE;
        }

        if (Tenant::query()->where('name', $name)->orWhere('slug', $slug)->exists()) {
            $this->error("Tenant [{$name}] already exists.");

            return self::FAILURE;
        }

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'active' => true,
            'modules' => Modules::defaults(),
        ]);

        $this->info('Tenant created.');
        $this->line("ID: {$tenant->id}");
        $this->line("Slug: {$tenant->slug}");

        $email = mb_strtolower(trim((string) $this->option('admin-email')));
        $password = (string) $this->option('admin-password');

        if ($email !== '' && $password !== '') {
            $user = User::query()->where('email', $email)->first()
                ?? User::create([
                    'name' => (string) $this->option('admin-name'),
                    'email' => $email,
                    'password' => $password,
                    'must_change_password' => true,
                ]);

            $user->tenants()->syncWithoutDetaching([$tenant->id]);

            $tenant->run(function () use ($user): void {
                $user->assignRole(Permissions::ROLE_ADMIN);
            });

            $this->line("Admin: {$user->email}");
        }

        return self::SUCCESS;
    }
}
