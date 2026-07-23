<?php

use App\Support\ScheduleSettings;
use App\Support\WorkingHours;

it('rounds the span out to whole hours', function (): void {
    $settings = ScheduleSettings::forSpan(510, 1050); // 08:30 - 17:30

    expect($settings['start_hour'])->toBe(8)
        ->and($settings['end_hour'])->toBe(18);
});

it('keeps an exact hourly span untouched', function (): void {
    $settings = ScheduleSettings::forSpan(480, 1080); // 08:00 - 18:00

    expect($settings['start_hour'])->toBe(8)
        ->and($settings['end_hour'])->toBe(18);
});

it('widens a span that is shorter than the minimum', function (): void {
    $settings = ScheduleSettings::forSpan(540, 600); // 09:00 - 10:00

    expect($settings['end_hour'] - $settings['start_hour'])->toBe(ScheduleSettings::MIN_SPAN_HOURS)
        ->and($settings['start_hour'])->toBe(9);
});

it('pulls the start back when widening would pass midnight', function (): void {
    $settings = ScheduleSettings::forSpan(1380, 1440); // 23:00 - 24:00

    expect($settings['end_hour'])->toBe(24)
        ->and($settings['start_hour'])->toBe(24 - ScheduleSettings::MIN_SPAN_HOURS);
});

it('reports the span across every configured weekday', function (): void {
    $hours = WorkingHours::fromRanges([
        ['weekday' => 1, 'start' => 600, 'end' => 960],
        ['weekday' => 3, 'start' => 360, 'end' => 720],
        ['weekday' => 5, 'start' => 540, 'end' => 1020],
    ]);

    expect($hours->span())->toBe([360, 1020]);
});

it('has no span without configured hours', function (): void {
    expect(WorkingHours::fromRanges([])->span())->toBeNull();
});
