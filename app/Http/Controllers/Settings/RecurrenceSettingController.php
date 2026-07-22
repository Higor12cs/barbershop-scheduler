<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\NotificationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RecurrenceSettingController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Settings/Recurrence/Index', [
            'settings' => [
                'recurrence_horizon_days' => NotificationSetting::current()->recurrence_horizon_days,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recurrence_horizon_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        NotificationSetting::current()->update($data);

        return back()->with('success', 'Configurações salvas com sucesso!');
    }
}
