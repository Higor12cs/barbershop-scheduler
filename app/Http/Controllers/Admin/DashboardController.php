<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tenants = Tenant::query()->get();

        $active = $tenants->filter(fn (Tenant $tenant): bool => ! $tenant->isBlocked())->count();
        $expired = $tenants->filter(fn (Tenant $tenant): bool => $tenant->accessExpired())->count();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'tenants' => $tenants->count(),
                'active' => $active,
                'expired' => $expired,
                'users' => User::query()->count(),
            ],
        ]);
    }
}
