<?php

use App\Support\NotificationWindow;
use Illuminate\Support\Carbon;

function window(): NotificationWindow
{
    return new NotificationWindow(480, 1200);
}

it('keeps sends that already fall inside the window', function (): void {
    $due = window()->dueAt(Carbon::parse('2026-07-22 16:00'), 180);

    expect($due->toDateTimeString())->toBe('2026-07-22 13:00:00');
});

it('anticipates to the previous evening when the send would land before the window opens', function (): void {
    $due = window()->dueAt(Carbon::parse('2026-07-22 08:00'), 120);

    expect($due->toDateTimeString())->toBe('2026-07-21 20:00:00');
});

it('anticipates to the same evening when the send would land after the window closes', function (): void {
    $due = window()->dueAt(Carbon::parse('2026-07-22 23:00'), 60);

    expect($due->toDateTimeString())->toBe('2026-07-22 20:00:00');
});

it('keeps a send that lands exactly on the window bounds', function (): void {
    expect(window()->dueAt(Carbon::parse('2026-07-22 10:00'), 120)->toDateTimeString())->toBe('2026-07-22 08:00:00')
        ->and(window()->dueAt(Carbon::parse('2026-07-22 22:00'), 120)->toDateTimeString())->toBe('2026-07-22 20:00:00');
});

it('anticipates a day-ahead confirmation into the previous evening', function (): void {
    $due = window()->dueAt(Carbon::parse('2026-07-22 07:30'), 1440);

    expect($due->toDateTimeString())->toBe('2026-07-20 20:00:00');
});

it('reports the window as closed outside business hours', function (): void {
    expect(window()->isOpen(Carbon::parse('2026-07-22 07:59')))->toBeFalse()
        ->and(window()->isOpen(Carbon::parse('2026-07-22 08:00')))->toBeTrue()
        ->and(window()->isOpen(Carbon::parse('2026-07-22 20:00')))->toBeTrue()
        ->and(window()->isOpen(Carbon::parse('2026-07-22 20:01')))->toBeFalse();
});

it('looks far enough ahead to catch anticipated sends', function (): void {
    expect(window()->lookaheadMinutes(180))->toBe(1620);
});
