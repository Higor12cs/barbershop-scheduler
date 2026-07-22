<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $user = $request->user();

        if (mb_strlen($term) < 1) {
            return response()->json(['customers' => [], 'employees' => []]);
        }

        $customers = $user?->can('customers.view')
            ? Customer::query()
                ->where('active', true)
                ->where(function ($query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'phone'])
                ->all()
            : [];

        $employees = $user?->can('employees.view')
            ? Employee::query()
                ->where('active', true)
                ->where('name', 'like', "%{$term}%")
                ->orderBy('name')
                ->limit(6)
                ->get(['id', 'name', 'color'])
                ->all()
            : [];

        return response()->json([
            'customers' => $customers,
            'employees' => $employees,
        ]);
    }
}
