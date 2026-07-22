<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MessageSettingController extends Controller
{
    public function show(): Response
    {
        $settings = NotificationSetting::current();

        return Inertia::render('Settings/Messages/Index', [
            'settings' => [
                'booking_enabled' => $settings->booking_enabled,
                'booking_template' => $settings->booking_template,
                'reminder_enabled' => $settings->reminder_enabled,
                'reminder_minutes_before' => $settings->reminder_minutes_before,
                'reminder_template' => $settings->reminder_template,
                'confirmation_enabled' => $settings->confirmation_enabled,
                'confirmation_minutes_before' => $settings->confirmation_minutes_before,
                'confirmation_template' => $settings->confirmation_template,
                'recurrence_horizon_days' => $settings->recurrence_horizon_days,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_enabled' => ['boolean'],
            'booking_template' => ['required', 'string', 'max:1000'],
            'reminder_enabled' => ['boolean'],
            'reminder_minutes_before' => ['required', 'integer', 'min:1', 'max:10080'],
            'reminder_template' => ['required', 'string', 'max:1000'],
            'confirmation_enabled' => ['boolean'],
            'confirmation_minutes_before' => ['required', 'integer', 'min:1', 'max:10080'],
            'confirmation_template' => ['required', 'string', 'max:1000'],
            'recurrence_horizon_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        NotificationSetting::current()->update([
            'booking_enabled' => $data['booking_enabled'] ?? false,
            'booking_template' => $data['booking_template'],
            'reminder_enabled' => $data['reminder_enabled'] ?? false,
            'reminder_minutes_before' => $data['reminder_minutes_before'],
            'reminder_template' => $data['reminder_template'],
            'confirmation_enabled' => $data['confirmation_enabled'] ?? false,
            'confirmation_minutes_before' => $data['confirmation_minutes_before'],
            'confirmation_template' => $data['confirmation_template'],
            'recurrence_horizon_days' => $data['recurrence_horizon_days'],
        ]);

        return back()->with('success', 'Configurações salvas com sucesso!');
    }
}
