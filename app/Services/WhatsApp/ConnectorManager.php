<?php

namespace App\Services\WhatsApp;

use App\Enums\WhatsAppProvider;
use App\Models\Tenant;

class ConnectorManager
{
    public function forCurrent(): ?WhatsAppConnector
    {
        $tenant = tenant();

        return $tenant instanceof Tenant ? $this->forTenant($tenant) : null;
    }

    public function forTenant(Tenant $tenant): ?WhatsAppConnector
    {
        $provider = WhatsAppProvider::tryFrom((string) $tenant->whatsapp_provider);

        if ($provider === null) {
            return null;
        }

        $config = is_array($tenant->whatsapp_config) ? $tenant->whatsapp_config : [];

        return match ($provider) {
            WhatsAppProvider::DiChat => new DiChatConnector($config),
            WhatsAppProvider::Log => new LogConnector($config),
        };
    }
}
