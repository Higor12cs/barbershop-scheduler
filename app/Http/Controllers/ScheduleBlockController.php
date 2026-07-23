<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduleBlockRequest;
use App\Models\Employee;
use App\Models\ScheduleBlock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleBlockController extends Controller
{
    public function index(Request $request): Response
    {
        $employeeId = $request->integer('employee_id') ?: null;

        $blocks = ScheduleBlock::query()
            ->with('employee:id,name,color')
            ->when($employeeId !== null, fn ($query) => $query
                ->where(fn ($query) => $query->whereNull('employee_id')->orWhere('employee_id', $employeeId)))
            ->orderByDesc('starts_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (ScheduleBlock $block): array => $this->serialize($block));

        return Inertia::render('Blocks/Index', [
            'blocks' => $blocks,
            'filters' => ['employee_id' => $employeeId],
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Blocks/Create', [
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function store(ScheduleBlockRequest $request): RedirectResponse
    {
        ScheduleBlock::create($request->blockAttributes());

        return to_route('blocks.index')->with('success', 'Bloqueio cadastrado com sucesso!');
    }

    public function edit(ScheduleBlock $block): Response
    {
        return Inertia::render('Blocks/Edit', [
            'block' => [
                'id' => $block->id,
                'employee_id' => $block->employee_id,
                'all_day' => $block->all_day,
                'reason' => $block->reason,
                'start_date' => $block->starts_at->toDateString(),
                'start_time' => $block->starts_at->format('H:i'),
                'end_date' => $this->endDate($block),
                'end_time' => $block->ends_at->format('H:i'),
            ],
            'employees' => $this->employeeOptions(),
        ]);
    }

    public function update(ScheduleBlockRequest $request, ScheduleBlock $block): RedirectResponse
    {
        $block->update($request->blockAttributes());

        return to_route('blocks.index')->with('success', 'Bloqueio atualizado com sucesso!');
    }

    public function destroy(ScheduleBlock $block): RedirectResponse
    {
        $block->delete();

        return to_route('blocks.index')->with('success', 'Bloqueio removido com sucesso!');
    }

    /**
     * All-day blocks are stored with an exclusive end, so the last blocked day
     * is the day before `ends_at`.
     */
    private function endDate(ScheduleBlock $block): string
    {
        return $block->all_day
            ? $block->ends_at->copy()->subDay()->toDateString()
            : $block->ends_at->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ScheduleBlock $block): array
    {
        return [
            'id' => $block->id,
            'employee_id' => $block->employee_id,
            'employee_name' => $block->employee?->name,
            'employee_color' => $block->employee?->color,
            'all_day' => $block->all_day,
            'reason' => $block->reason,
            'start_date' => $block->starts_at->toDateString(),
            'start_time' => $block->starts_at->format('H:i'),
            'end_date' => $this->endDate($block),
            'end_time' => $block->ends_at->format('H:i'),
            'is_past' => $block->ends_at->isPast(),
        ];
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function employeeOptions(): array
    {
        return Employee::query()
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Employee $employee): array => [
                'value' => $employee->id,
                'label' => $employee->name,
            ])
            ->all();
    }
}
