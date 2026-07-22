<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyBySession
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->session()->get('tenant_id');
        $user = $request->user();

        if (! is_string($tenantId) || ! $user) {
            return to_route('tenant-selection.index');
        }

        $tenant = $user->findAccessibleTenant($tenantId);

        if (! $tenant) {
            $request->session()->forget('tenant_id');

            return to_route('tenant-selection.index')
                ->with('warning', 'Selecione um ambiente para continuar.');
        }

        tenancy()->initialize($tenant);

        return $next($request);
    }
}
