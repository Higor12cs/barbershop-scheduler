<?php

namespace App\Models;

use App\Support\Modules;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'slug', 'access_until', 'active', 'modules', 'whatsapp_provider', 'whatsapp_config', 'webhook_secret'];
    }

    protected function casts(): array
    {
        return [
            'access_until' => 'date',
            'active' => 'boolean',
            'modules' => 'array',
            'whatsapp_config' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant): void {
            if (blank($tenant->webhook_secret)) {
                $tenant->webhook_secret = Str::uuid()->toString();
            }
        });
    }

    public function hasWhatsAppProvider(): bool
    {
        return filled($this->whatsapp_provider);
    }

    public function enabledModules(): array
    {
        $modules = $this->modules;

        return is_array($modules) ? array_values($modules) : Modules::defaults();
    }

    public function hasModule(string $key): bool
    {
        return ! Modules::isToggleable($key) || in_array($key, $this->enabledModules(), true);
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function accessExpired(): bool
    {
        $until = $this->access_until;

        return $until !== null && Carbon::parse($until)->endOfDay()->isPast();
    }

    public function isBlocked(): bool
    {
        return ! $this->isActive() || $this->accessExpired();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withTimestamps();
    }
}
