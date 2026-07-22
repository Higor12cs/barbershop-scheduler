<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\EmployeeRequest;
use App\Models\Appointment;
use App\Models\Employee;
use App\Models\Sale;
use App\Support\EmployeeColors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $employees = Employee::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'color' => $employee->color,
                'active' => $employee->active,
            ]);

        return Inertia::render('Employees/Index', [
            'employees' => $employees,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Employees/Create', [
            'colors' => EmployeeColors::options(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        Employee::create($request->validated());

        return to_route('employees.index')->with('success', 'Funcionário cadastrado com sucesso!');
    }

    public function show(Employee $employee): Response
    {
        $appointments = $employee->appointments()
            ->with(['customer:id,name,phone', 'product:id,name'])
            ->orderByDesc('starts_at')
            ->get();

        $sales = $employee->sales()->with('customer:id,name')->orderByDesc('sold_at')->get();

        $completed = $appointments->where('status', AppointmentStatus::Completed);
        $cancelled = $appointments->where('status', AppointmentStatus::Cancelled)->count();
        $noShow = $appointments->where('status', AppointmentStatus::NoShow)->count();
        $decided = $completed->count() + $cancelled + $noShow;

        $revenue = (float) $sales->sum('total');
        $salesCount = $sales->count();

        $topService = $completed
            ->filter(fn (Appointment $appointment): bool => $appointment->product_id !== null)
            ->groupBy('product_id')
            ->map(fn ($group): array => ['name' => $group->first()->product?->name, 'count' => $group->count()])
            ->sortByDesc('count')
            ->first();

        $now = now();
        $upcoming = $appointments
            ->filter(fn (Appointment $appointment): bool => in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)
                && $appointment->starts_at->greaterThanOrEqualTo($now))
            ->sortBy('starts_at')
            ->take(5)
            ->map(fn (Appointment $appointment): array => $this->appointmentRow($appointment))
            ->values()
            ->all();

        $statusCounts = [];

        foreach (AppointmentStatus::cases() as $status) {
            $statusCounts[] = [
                'status' => $status->value,
                'label' => $status->label(),
                'count' => $appointments->where('status', $status)->count(),
            ];
        }

        return Inertia::render('Employees/Show', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'color' => $employee->color,
                'active' => $employee->active,
            ],
            'stats' => [
                'revenue' => $revenue,
                'completed' => $completed->count(),
                'average_ticket' => $salesCount > 0 ? round($revenue / $salesCount, 2) : 0.0,
                'attendance_rate' => $decided > 0 ? round($completed->count() / $decided * 100, 1) : null,
                'upcoming' => count($upcoming),
                'top_service' => $topService['name'] ?? null,
            ],
            'statusCounts' => $statusCounts,
            'upcoming' => $upcoming,
            'recentAppointments' => $appointments->take(6)
                ->map(fn (Appointment $appointment): array => $this->appointmentRow($appointment))
                ->values()
                ->all(),
            'recentSales' => $sales->take(6)
                ->map(fn (Sale $sale): array => [
                    'id' => $sale->id,
                    'date' => $sale->sold_at->format('d/m/Y'),
                    'customer' => $sale->customer?->name,
                    'total' => (float) $sale->total,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        return Inertia::render('Employees/Edit', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'phone' => $employee->phone,
                'email' => $employee->email,
                'color' => $employee->color,
                'active' => $employee->active,
            ],
            'colors' => EmployeeColors::options(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return to_route('employees.index')->with('success', 'Funcionário atualizado com sucesso!');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return to_route('employees.index')->with('success', 'Funcionário removido com sucesso!');
    }

    /**
     * @return array<string, mixed>
     */
    private function appointmentRow(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'date' => $appointment->starts_at->format('d/m/Y'),
            'time' => $appointment->starts_at->format('H:i'),
            'service' => $appointment->product?->name,
            'customer' => $appointment->customer?->name,
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'price' => (float) $appointment->price,
        ];
    }
}
