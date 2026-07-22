<?php

namespace App\Tenancy;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class PermissionsBootstrapper implements TenancyBootstrapper
{
    public function __construct(private PermissionRegistrar $registrar) {}

    public function bootstrap(Tenant $tenant): void
    {
        $this->registrar->setPermissionsTeamId($tenant->getTenantKey());
        $this->registrar->forgetCachedPermissions();
    }

    public function revert(): void
    {
        $this->registrar->setPermissionsTeamId(null);
        $this->registrar->forgetCachedPermissions();
    }
}
