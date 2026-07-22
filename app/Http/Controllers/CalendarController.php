<?php

namespace App\Http\Controllers;

use App\Models\ShortLink;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CalendarController extends Controller
{
    public function show(string $code): View|RedirectResponse
    {
        $link = ShortLink::query()->where('code', $code)->firstOrFail();

        if (! $link->isCalendar()) {
            abort_unless(filled($link->target_url), 404);

            return redirect()->away($link->target_url, 302);
        }

        $payload = $link->payload;
        $startsAt = Carbon::parse($payload['starts_at']);
        $endsAt = Carbon::parse($payload['ends_at']);

        return view('calendar', [
            'title' => (string) ($payload['title'] ?? 'Agendamento'),
            'location' => (string) ($payload['location'] ?? ''),
            'description' => (string) ($payload['description'] ?? ''),
            'when' => $startsAt->format('d/m/Y').' às '.$startsAt->format('H:i'),
            'googleUrl' => $this->googleUrl($payload, $startsAt, $endsAt),
            'icsUrl' => url('/l/'.$link->code.'.ics'),
        ]);
    }

    public function ics(string $code): Response
    {
        $link = ShortLink::query()->where('code', $code)->firstOrFail();

        abort_unless($link->isCalendar(), 404);

        $payload = $link->payload;
        $startsAt = Carbon::parse($payload['starts_at']);
        $endsAt = Carbon::parse($payload['ends_at']);

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('app.name').'//PT-BR//EN',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$link->code.'@'.Str::slug(config('app.name')),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$startsAt->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$endsAt->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape((string) ($payload['title'] ?? 'Agendamento')),
            'DESCRIPTION:'.$this->escape((string) ($payload['description'] ?? '')),
            'LOCATION:'.$this->escape((string) ($payload['location'] ?? '')),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines)."\r\n", 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="agendamento.ics"',
        ]);
    }

    private function googleUrl(array $payload, Carbon $startsAt, Carbon $endsAt): string
    {
        return 'https://calendar.google.com/calendar/render?'.http_build_query([
            'action' => 'TEMPLATE',
            'text' => (string) ($payload['title'] ?? 'Agendamento'),
            'dates' => $startsAt->utc()->format('Ymd\THis\Z').'/'.$endsAt->utc()->format('Ymd\THis\Z'),
            'details' => (string) ($payload['description'] ?? ''),
            'location' => (string) ($payload['location'] ?? ''),
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\,', '\;', '\n'], $value);
    }
}
