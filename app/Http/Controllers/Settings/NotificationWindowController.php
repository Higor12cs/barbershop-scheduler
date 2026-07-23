<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use App\Support\TimeOfDay;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationWindowController extends Controller
{
    public function show(): Response
    {
        $settings = NotificationSetting::current();

        return Inertia::render('Settings/Messages/Window', [
            'settings' => [
                'send_window_start' => TimeOfDay::toString($settings->send_window_start),
                'send_window_end' => TimeOfDay::toString($settings->send_window_end),
                'reminder_enabled' => $settings->reminder_enabled,
                'reminder_minutes_before' => $settings->reminder_minutes_before,
                'confirmation_enabled' => $settings->confirmation_enabled,
                'confirmation_minutes_before' => $settings->confirmation_minutes_before,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'send_window_start' => ['required', 'date_format:H:i'],
            'send_window_end' => ['required', 'date_format:H:i'],
        ], [], [
            'send_window_start' => 'início da janela',
            'send_window_end' => 'fim da janela',
        ]);

        $start = TimeOfDay::toMinutes($data['send_window_start']);
        $end = TimeOfDay::toMinutes($data['send_window_end']);

        if ($end <= $start) {
            return back()->withErrors(['send_window_end' => 'O fim da janela deve ser depois do início.']);
        }

        NotificationSetting::current()->update([
            'send_window_start' => $start,
            'send_window_end' => $end,
        ]);

        return back()->with('success', 'Janela de envio salva com sucesso!');
    }
}
