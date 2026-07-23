<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeScheduleRequest;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Support\ScheduleSettings;
use App\Support\TimeOfDay;
use App\Support\Weekdays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeScheduleController extends Controller
{
    public function edit(Employee $employee): Response
    {
        $ranges = $employee->schedules()
            ->orderBy('start_minutes')
            ->get()
            ->groupBy('weekday');

        return Inertia::render('Employees/Schedule', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'color' => $employee->color,
            ],
            'days' => array_map(fn (int $weekday): array => [
                'weekday' => $weekday,
                'label' => Weekdays::label($weekday),
                'ranges' => $ranges->get($weekday, collect())
                    ->map(fn (EmployeeSchedule $schedule): array => [
                        'start' => TimeOfDay::toString($schedule->start_minutes),
                        'end' => TimeOfDay::toString($schedule->end_minutes),
                    ])
                    ->values()
                    ->all(),
            ], Weekdays::ordered()),
            'defaults' => [
                'start' => TimeOfDay::toString(ScheduleSettings::START_HOUR * 60),
                'end' => TimeOfDay::toString(ScheduleSettings::END_HOUR * 60),
            ],
        ]);
    }

    public function update(EmployeeScheduleRequest $request, Employee $employee): RedirectResponse
    {
        $rows = $request->scheduleRows();

        DB::transaction(function () use ($employee, $rows): void {
            $employee->schedules()->delete();

            if ($rows !== []) {
                $employee->schedules()->createMany($rows);
            }
        });

        return back()->with('success', 'Jornada de trabalho salva com sucesso!');
    }
}
