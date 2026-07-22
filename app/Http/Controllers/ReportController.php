<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\Period;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = $request->string('tab')->toString();
        $tab = in_array($tab, ['sales', 'appointments'], true) ? $tab : 'sales';

        $filters = Period::fromRequest($request);
        $employeeId = $request->integer('employee_id') ?: null;
        $customerId = $request->integer('customer_id') ?: null;

        return Inertia::render('Reports/Index', [
            'tab' => $tab,
            'filters' => [
                'period' => $filters['period'],
                'date_from' => $filters['from']->toDateString(),
                'date_to' => $filters['to']->toDateString(),
                'employee_id' => $employeeId,
                'customer_id' => $customerId,
            ],
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Employee $employee): array => [
                    'value' => $employee->id,
                    'label' => $employee->name,
                ])->all(),
            'customers' => Customer::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Customer $customer): array => [
                    'value' => $customer->id,
                    'label' => $customer->name,
                ])->all(),
            'sales' => $tab === 'sales'
                ? $this->salesReport($filters['from'], $filters['to'], $employeeId, $customerId)
                : null,
            'appointments' => $tab === 'appointments'
                ? $this->appointmentsReport($filters['from'], $filters['to'], $employeeId, $customerId)
                : null,
        ]);
    }

    public function salesPdf(Request $request): HttpResponse
    {
        $filters = Period::fromRequest($request);
        $employeeId = $request->integer('employee_id') ?: null;
        $customerId = $request->integer('customer_id') ?: null;

        $pdf = Pdf::loadView('pdf.sales', [
            'sales' => $this->salesReport($filters['from'], $filters['to'], $employeeId, $customerId),
            'tenantName' => $this->tenantName(),
            'periodLabel' => $this->periodLabel($filters['from'], $filters['to'], $employeeId, $customerId),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download($this->filename('relatorio-vendas'));
    }

    public function appointmentsPdf(Request $request): HttpResponse
    {
        $filters = Period::fromRequest($request);
        $employeeId = $request->integer('employee_id') ?: null;
        $customerId = $request->integer('customer_id') ?: null;

        $pdf = Pdf::loadView('pdf.appointments', [
            'appointments' => $this->appointmentsReport($filters['from'], $filters['to'], $employeeId, $customerId),
            'tenantName' => $this->tenantName(),
            'periodLabel' => $this->periodLabel($filters['from'], $filters['to'], $employeeId, $customerId),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download($this->filename('relatorio-agendamentos'));
    }

    private function tenantName(): string
    {
        return (string) (tenant('name') ?? config('app.name'));
    }

    private function periodLabel(Carbon $from, Carbon $to, ?int $employeeId, ?int $customerId): string
    {
        $label = $from->format('d/m/Y').' a '.$to->format('d/m/Y');

        if ($employeeId !== null) {
            $employee = Employee::query()->find($employeeId, ['name']);

            if ($employee !== null) {
                $label .= ' · '.$employee->name;
            }
        }

        if ($customerId !== null) {
            $customer = Customer::query()->find($customerId, ['name']);

            if ($customer !== null) {
                $label .= ' · '.$customer->name;
            }
        }

        return $label;
    }

    private function filename(string $prefix): string
    {
        return $prefix.'-'.now()->format('Y-m-d').'.pdf';
    }

    private function salesReport(Carbon $from, Carbon $to, ?int $employeeId, ?int $customerId): array
    {
        $sales = Sale::query()
            ->whereBetween('sold_at', [$from, $to])
            ->when($employeeId !== null, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->with('employee:id,name')
            ->withCount('items')
            ->get();

        $total = (float) $sales->sum('total');
        $count = $sales->count();

        $byEmployee = $sales
            ->groupBy(fn (Sale $sale): string => (string) ($sale->employee_id ?? 0))
            ->map(function (Collection $group) use ($total): array {
                $groupTotal = (float) $group->sum('total');

                return [
                    'name' => $group->first()->employee?->name ?? 'Sem Funcionário',
                    'count' => $group->count(),
                    'items' => (int) $group->sum('items_count'),
                    'total' => $groupTotal,
                    'average' => round($groupTotal / $group->count(), 2),
                    'percent' => $total > 0 ? round($groupTotal / $total * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        $byProduct = SaleItem::query()
            ->whereHas('sale', function ($query) use ($from, $to, $employeeId, $customerId): void {
                $query->whereBetween('sold_at', [$from, $to])
                    ->when($employeeId !== null, fn ($query) => $query->where('employee_id', $employeeId))
                    ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId));
            })
            ->get()
            ->groupBy('description')
            ->map(fn (Collection $group, string $description): array => [
                'name' => $description,
                'quantity' => (int) $group->sum('quantity'),
                'total' => (float) $group->sum('total'),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'summary' => [
                'total' => $total,
                'count' => $count,
                'average' => $count > 0 ? round($total / $count, 2) : 0.0,
            ],
            'byEmployee' => $byEmployee,
            'byProduct' => $byProduct,
            'chart' => $this->dailyChart($from, $to, $sales->groupBy(fn (Sale $sale): string => $sale->sold_at->toDateString())
                ->map(fn (Collection $group): float => (float) $group->sum('total'))),
        ];
    }

    private function appointmentsReport(Carbon $from, Carbon $to, ?int $employeeId, ?int $customerId): array
    {
        $appointments = Appointment::query()
            ->whereBetween('starts_at', [$from, $to])
            ->when($employeeId !== null, fn ($query) => $query->where('employee_id', $employeeId))
            ->when($customerId !== null, fn ($query) => $query->where('customer_id', $customerId))
            ->with('employee:id,name')
            ->get();

        $statusCounts = [];

        foreach (AppointmentStatus::cases() as $status) {
            $statusCounts[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $appointments->where('status', $status)->count(),
            ];
        }

        $byEmployee = $appointments
            ->groupBy('employee_id')
            ->map(function (Collection $group): array {
                $counts = [];

                foreach (AppointmentStatus::cases() as $status) {
                    $counts[$status->value] = $group->where('status', $status)->count();
                }

                return [
                    'name' => $group->first()->employee?->name ?? '—',
                    ...$counts,
                    'completion_rate' => $this->attendanceRate($counts),
                ];
            })
            ->sortByDesc('completed')
            ->values()
            ->all();

        $totals = collect($statusCounts)->pluck('count', 'status')->all();

        return [
            'summary' => [
                'total' => $appointments->count(),
                'statuses' => $statusCounts,
                'attendance_rate' => $this->attendanceRate($totals),
            ],
            'byEmployee' => $byEmployee,
            'chart' => $this->dailyChart($from, $to, $appointments->groupBy(fn (Appointment $appointment): string => $appointment->starts_at->toDateString())
                ->map(fn (Collection $group): int => $group->count())),
        ];
    }

    private function attendanceRate(array $counts): ?float
    {
        $completed = $counts[AppointmentStatus::Completed->value] ?? 0;
        $decided = $completed
            + ($counts[AppointmentStatus::Cancelled->value] ?? 0)
            + ($counts[AppointmentStatus::NoShow->value] ?? 0);

        return $decided > 0 ? round($completed / $decided * 100, 1) : null;
    }

    private function dailyChart(Carbon $from, Carbon $to, Collection $valuesByDate): array
    {
        $bars = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $bars[] = [
                'label' => $cursor->format('d/m'),
                'value' => (float) ($valuesByDate[$cursor->toDateString()] ?? 0),
            ];

            $cursor->addDay();
        }

        return $bars;
    }
}
