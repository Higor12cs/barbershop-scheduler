<?php

namespace App\Http\Middleware;

use App\Support\Modules;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $module = Modules::forRoute($request->route()?->getName());

        if ($module !== null && ! (tenant()?->hasModule($module) ?? false)) {
            abort(404);
        }

        return $next($request);
    }
}
