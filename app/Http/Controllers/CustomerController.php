<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\CustomerRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'active' => $customer->active,
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return to_route('customers.index')->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function show(Customer $customer): Response
    {
        $appointments = $customer->appointments()
            ->with(['employee:id,name,color', 'product:id,name'])
            ->orderByDesc('starts_at')
            ->get();

        $sales = $customer->sales()->orderByDesc('sold_at')->get();

        $completed = $appointments->where('status', AppointmentStatus::Completed);
        $cancelled = $appointments->where('status', AppointmentStatus::Cancelled)->count();
        $noShow = $appointments->where('status', AppointmentStatus::NoShow)->count();
        $decided = $completed->count() + $cancelled + $noShow;

        $totalSpent = (float) $sales->sum('total');
        $salesCount = $sales->count();

        $favorite = $appointments
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

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'birth_date' => $customer->birth_date?->format('Y-m-d'),
                'notes' => $customer->notes,
                'active' => $customer->active,
            ],
            'stats' => [
                'total_spent' => $totalSpent,
                'visits' => $completed->count(),
                'average_ticket' => $salesCount > 0 ? round($totalSpent / $salesCount, 2) : 0.0,
                'last_visit' => $completed->sortByDesc('starts_at')->first()?->starts_at?->format('d/m/Y'),
                'attendance_rate' => $decided > 0 ? round($completed->count() / $decided * 100, 1) : null,
                'favorite_service' => $favorite['name'] ?? null,
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
                    'total' => (float) $sale->total,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'birth_date' => $customer->birth_date?->format('Y-m-d'),
                'notes' => $customer->notes,
                'active' => $customer->active,
            ],
        ]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.index')->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return to_route('customers.index')->with('success', 'Cliente removido com sucesso!');
    }

    public function quickStore(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return response()->json($this->quickPayload($customer));
    }

    public function quickUpdate(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return response()->json($this->quickPayload($customer));
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
            'employee' => $appointment->employee?->name,
            'employee_color' => $appointment->employee?->color,
            'status' => $appointment->status->value,
            'status_label' => $appointment->status->label(),
            'price' => (float) $appointment->price,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function quickPayload(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'birth_date' => $customer->birth_date?->format('Y-m-d'),
        ];
    }
}
