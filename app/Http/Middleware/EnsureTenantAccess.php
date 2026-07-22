<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && $tenant->isBlocked()) {
            return Inertia::render('TenantBlocked', [
                'reason' => $tenant->isActive() ? 'expired' : 'disabled',
                'tenantName' => $tenant->name,
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
