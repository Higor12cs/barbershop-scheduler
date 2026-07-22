<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Database\Seeders\Tenant\RolePermissionSeeder;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('permissions:sync')]
class SyncPermissionsCommand extends Command
{
    public function handle(): int
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->run(fn () => (new RolePermissionSeeder)->run());
            $this->line("Synced: {$tenant->name}");
        }

        $this->info("Permissions synced for {$tenants->count()} tenant(s).");

        return self::SUCCESS;
    }
}
