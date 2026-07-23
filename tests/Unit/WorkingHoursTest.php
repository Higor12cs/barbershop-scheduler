<?php

use App\Support\WorkingHours;
use Illuminate\Support\Carbon;

/**
 * 2026-07-22 is a Wednesday (weekday 3).
 */
function wednesdayHours(): WorkingHours
{
    return WorkingHours::fromRanges([
        ['weekday' => 3, 'start' => 540, 'end' => 720],
        ['weekday' => 3, 'start' => 840, 'end' => 1080],
    ]);
}

it('is empty when no ranges are configured', function (): void {
    expect(WorkingHours::fromRanges([])->isEmpty())->toBeTrue();
});

it('discards ranges that end before they start', function (): void {
    $hours = WorkingHours::fromRanges([['weekday' => 3, 'start' => 720, 'end' => 540]]);

    expect($hours->isEmpty())->toBeTrue();
});

it('merges overlapping ranges of the same day', function (): void {
    $hours = WorkingHours::fromRanges([
        ['weekday' => 3, 'start' => 480, 'end' => 720],
        ['weekday' => 3, 'start' => 690, 'end' => 1080],
    ]);

    expect($hours->forWeekday(3))->toBe([['start' => 480, 'end' => 1080]]);
});

it('covers an appointment fully inside a working range', function (): void {
    $covered = wednesdayHours()->covers(
        Carbon::parse('2026-07-22 10:00'),
        Carbon::parse('2026-07-22 10:30'),
    );

    expect($covered)->toBeTrue();
});

it('rejects an appointment that falls in the lunch break', function (): void {
    $covered = wednesdayHours()->covers(
        Carbon::parse('2026-07-22 12:30'),
        Carbon::parse('2026-07-22 13:00'),
    );

    expect($covered)->toBeFalse();
});

it('rejects an appointment that starts inside but spills past the range', function (): void {
    $covered = wednesdayHours()->covers(
        Carbon::parse('2026-07-22 11:45'),
        Carbon::parse('2026-07-22 12:30'),
    );

    expect($covered)->toBeFalse();
});

it('rejects any appointment on a day off', function (): void {
    $covered = wednesdayHours()->covers(
        Carbon::parse('2026-07-23 10:00'),
        Carbon::parse('2026-07-23 10:30'),
    );

    expect($covered)->toBeFalse();
});

it('reports the gaps around the working ranges', function (): void {
    $gaps = wednesdayHours()->gapsOn(Carbon::parse('2026-07-22'), 420, 1260);

    expect($gaps)->toBe([
        ['start' => 420, 'end' => 540],
        ['start' => 720, 'end' => 840],
        ['start' => 1080, 'end' => 1260],
    ]);
});

it('reports the whole grid as a gap on a day off', function (): void {
    $gaps = wednesdayHours()->gapsOn(Carbon::parse('2026-07-23'), 420, 1260);

    expect($gaps)->toBe([['start' => 420, 'end' => 1260]]);
});
