<?php

namespace App\Http\Middleware;

use App\Support\Permissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $permission = Permissions::forRoute($request->route()?->getName());

        if ($permission !== null) {
            abort_unless((bool) $request->user()?->can($permission), 403);
        }

        return $next($request);
    }
}
