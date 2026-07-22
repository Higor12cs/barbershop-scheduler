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
    public const TYPES = ['booking', 'reminder', 'confirmation'];

    /**
     * Types that are scheduled ahead of the appointment and therefore carry a
     * "minutes before" setting.
     */
    private const SCHEDULED_TYPES = ['reminder', 'confirmation'];

    public function show(string $type): Response
    {
        $settings = NotificationSetting::current();

        return Inertia::render('Settings/Messages/Show', [
            'type' => $type,
            'settings' => [
                'enabled' => $settings->{$type.'_enabled'},
                'template' => $settings->{$type.'_template'},
                'minutes_before' => $this->isScheduled($type) ? $settings->{$type.'_minutes_before'} : null,
            ],
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $data = $request->validate($this->rules($type));

        $payload = [
            $type.'_enabled' => $data['enabled'] ?? false,
            $type.'_template' => $data['template'],
        ];

        if ($this->isScheduled($type)) {
            $payload[$type.'_minutes_before'] = $data['minutes_before'];
        }

        NotificationSetting::current()->update($payload);

        return back()->with('success', 'Mensagem salva com sucesso!');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(string $type): array
    {
        $rules = [
            'enabled' => ['boolean'],
            'template' => ['required', 'string', 'max:1000'],
        ];

        if ($this->isScheduled($type)) {
            $rules['minutes_before'] = ['required', 'integer', 'min:1', 'max:10080'];
        }

        return $rules;
    }

    private function isScheduled(string $type): bool
    {
        return in_array($type, self::SCHEDULED_TYPES, true);
    }
}
