<?php

namespace App\Jobs;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupTenantPermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Tenant $tenant) {}

    public function handle(): void
    {
        $teamId = $this->tenant->getTenantKey();
        $connection = (new Role)->getConnection();

        $roleIds = Role::query()->where('tenant_id', $teamId)->pluck('id');

        $connection->table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
        $connection->table('model_has_roles')->where('tenant_id', $teamId)->delete();
        $connection->table('model_has_permissions')->where('tenant_id', $teamId)->delete();

        Role::query()->where('tenant_id', $teamId)->delete();
    }
}
