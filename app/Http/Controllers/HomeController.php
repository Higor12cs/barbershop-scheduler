<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Sale;
use App\Support\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $canViewSales = (tenant()?->hasModule(Modules::SALES) ?? false)
            && ($user->isSuperAdmin() || $user->can('sales.view'));
        $canViewForecast = (tenant()?->hasModule(Modules::REPORTS) ?? false)
            && ($user->isSuperAdmin() || $user->can('reports.view'));

        $todayAppointments = Appointment::query()
            ->whereBetween('starts_at', [now()->startOfDay(), now()->endOfDay()])
            ->get(['id', 'status']);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'today_total' => $todayAppointments->count(),
                'today_completed' => $todayAppointments->where('status', AppointmentStatus::Completed)->count(),
                'month_revenue' => $canViewSales ? $this->monthRevenue() : null,
                'forecast' => $canViewForecast ? $this->forecast() : null,
                'new_customers' => Customer::query()
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
            ],
            'todaySchedule' => $this->todaySchedule(),
            'revenueChart' => $canViewSales ? $this->revenueChart() : null,
            'birthdays' => $this->birthdays(),
        ]);
    }

    private function monthRevenue(): float
    {
        return (float) Sale::query()
            ->whereBetween('sold_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');
    }

    private function forecast(): float
    {
        return (float) Appointment::query()
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])
            ->whereBetween('starts_at', [now(), now()->addDays(30)])
            ->sum('price');
    }

    private function todaySchedule(): array
    {
        return Appointment::query()
            ->whereBetween('starts_at', [now(), now()->endOfDay()])
            ->whereIn('status', [AppointmentStatus::Scheduled->value, AppointmentStatus::Confirmed->value])
            ->with(['customer:id,name', 'employee:id,name,color', 'product:id,name'])
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (Appointment $appointment): array => [
                'id' => $appointment->id,
                'time' => $appointment->starts_at->format('H:i'),
                'customer_name' => $appointment->customer?->name,
                'product_name' => $appointment->product?->name,
                'employee_name' => $appointment->employee?->name,
                'employee_color' => $appointment->employee?->color,
                'status' => $appointment->status->value,
                'status_label' => $appointment->status->label(),
            ])->all();
    }

    private function revenueChart(): array
    {
        $from = now()->subDays(13)->startOfDay();

        $totalsByDay = Sale::query()
            ->where('sold_at', '>=', $from)
            ->get(['sold_at', 'total'])
            ->groupBy(fn (Sale $sale): string => $sale->sold_at->toDateString())
            ->map(fn (Collection $group): float => (float) $group->sum('total'));

        return collect(range(0, 13))
            ->map(function (int $offset) use ($from, $totalsByDay): array {
                $day = $from->copy()->addDays($offset);

                return [
                    'label' => $day->format('d/m'),
                    'value' => (float) ($totalsByDay[$day->toDateString()] ?? 0),
                ];
            })->all();
    }

    private function birthdays(): array
    {
        return Customer::query()
            ->where('active', true)
            ->whereMonth('birth_date', now()->month)
            ->orderByRaw('extract(day from birth_date)')
            ->get(['id', 'name', 'birth_date'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'day' => $customer->birth_date->format('d/m'),
            ])->all();
    }
}
