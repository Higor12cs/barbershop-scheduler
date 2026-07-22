<?php

namespace Database\Seeders\Tenant;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);

        if ($tenantId = tenant('id')) {
            $registrar->setPermissionsTeamId($tenantId);
        }

        $registrar->forgetCachedPermissions();

        foreach (Permissions::all() as $permission) {
            Permission::findOrCreate($permission, Permissions::GUARD);
        }

        Role::findOrCreate(Permissions::ROLE_ADMIN, Permissions::GUARD)
            ->syncPermissions(Permissions::all());
    }
}
