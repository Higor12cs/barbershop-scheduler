<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Http\Requests\RecurrenceRequest;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Recurrence;
use App\Services\RecurrenceGenerator;
use App\Support\ScheduleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurrenceController extends Controller
{
    public function __construct(private RecurrenceGenerator $generator) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $recurrences = Recurrence::query()
            ->with(['customer:id,name', 'employee:id,name', 'product:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Recurrence $recurrence): array => [
                'id' => $recurrence->id,
                'customer' => $recurrence->customer?->name,
                'employee' => $recurrence->employee?->name,
                'service' => $recurrence->product?->name,
                'interval_days' => $recurrence->interval_days,
                'time' => substr((string) $recurrence->time, 0, 5),
                'starts_on' => $recurrence->starts_on->toDateString(),
                'ends_on' => $recurrence->ends_on?->toDateString(),
                'active' => $recurrence->active,
            ]);

        return Inertia::render('Recurrences/Index', [
            'recurrences' => $recurrences,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Recurrences/Create', $this->formOptions());
    }

    public function store(RecurrenceRequest $request): RedirectResponse
    {
        $recurrence = Recurrence::create($request->validated());

        $summary = $this->generator->generate($recurrence);

        return to_route('recurrences.index')->with('success', $this->summaryMessage($summary));
    }

    public function edit(Recurrence $recurrence): Response
    {
        return Inertia::render('Recurrences/Edit', [
            'recurrence' => [
                'id' => $recurrence->id,
                'customer_id' => $recurrence->customer_id,
                'employee_id' => $recurrence->employee_id,
                'product_id' => $recurrence->product_id,
                'time' => substr((string) $recurrence->time, 0, 5),
                'interval_days' => $recurrence->interval_days,
                'starts_on' => $recurrence->starts_on->toDateString(),
                'ends_on' => $recurrence->ends_on?->toDateString(),
                'notes' => $recurrence->notes,
                'active' => $recurrence->active,
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(RecurrenceRequest $request, Recurrence $recurrence): RedirectResponse
    {
        $recurrence->update($request->validated());

        $this->generator->clearFutureScheduled($recurrence);

        $summary = RecurrenceGenerator::emptySummary();

        if ($recurrence->active) {
            $summary = $this->generator->generate($recurrence->fresh());
        }

        return to_route('recurrences.index')->with('success', $this->summaryMessage($summary));
    }

    public function destroy(Recurrence $recurrence): RedirectResponse
    {
        $this->generator->clearFutureScheduled($recurrence);

        $recurrence->delete();

        return to_route('recurrences.index')->with('success', 'Recorrência removida com sucesso.');
    }

    /**
     * @param  array{created: int, skipped_existing: int, skipped_conflicts: int, skipped_unavailable: int}  $summary
     */
    private function summaryMessage(array $summary): string
    {
        $message = "Recorrência salva. {$summary['created']} agendamentos gerados";

        $skipped = [];

        if ($summary['skipped_conflicts'] > 0) {
            $skipped[] = "{$summary['skipped_conflicts']} por conflito";
        }

        if ($summary['skipped_unavailable'] > 0) {
            $skipped[] = "{$summary['skipped_unavailable']} fora da jornada ou em bloqueio";
        }

        if ($skipped !== []) {
            $message .= ' ('.implode(', ', $skipped).' ignorados)';
        }

        return $message.'.';
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone'])
                ->map(fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                ])->all(),
            'employees' => Employee::query()
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Employee $employee): array => [
                    'value' => $employee->id,
                    'label' => $employee->name,
                ])->all(),
            'services' => Product::query()
                ->where('active', true)
                ->where('type', ProductType::Service->value)
                ->orderBy('name')
                ->get(['id', 'name', 'duration_minutes', 'price'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'duration_minutes' => (int) $product->duration_minutes,
                    'price' => (float) $product->price,
                ])->all(),
            'timeSlots' => $this->timeSlots(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function timeSlots(): array
    {
        $slots = [];

        for ($hour = ScheduleSettings::START_HOUR; $hour < ScheduleSettings::END_HOUR; $hour++) {
            for ($minute = 0; $minute < 60; $minute += ScheduleSettings::SLOT_MINUTES) {
                $slots[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return $slots;
    }
}
