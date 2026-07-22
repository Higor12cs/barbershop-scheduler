<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class Period
{
    public static function fromRequest(Request $request, string $default = 'month'): array
    {
        $period = $request->string('period')->toString();
        $period = in_array($period, ['today', 'week', 'month', 'custom'], true) ? $period : $default;

        if ($period === 'custom') {
            $from = ($request->date('date_from') ?? now()->startOfMonth())->startOfDay();
            $to = ($request->date('date_to') ?? now())->endOfDay();

            if ($from->greaterThan($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        } else {
            [$from, $to] = match ($period) {
                'today' => [now()->startOfDay(), now()->endOfDay()],
                'week' => [now()->startOfWeek(Carbon::MONDAY), now()->endOfWeek(Carbon::SUNDAY)],
                default => [now()->startOfMonth(), now()->endOfMonth()],
            };
        }

        return ['period' => $period, 'from' => $from, 'to' => $to];
    }
}
