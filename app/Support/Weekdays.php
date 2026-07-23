<?php

namespace App\Support;

/**
 * Weekday helpers using the same numbering as Carbon's `dayOfWeek` (0 = Sunday).
 */
class Weekdays
{
    private const LABELS = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    /**
     * Weekdays ordered for display, starting on Monday.
     *
     * @return array<int, int>
     */
    public static function ordered(): array
    {
        return [1, 2, 3, 4, 5, 6, 0];
    }

    public static function label(int $weekday): string
    {
        return self::LABELS[$weekday] ?? '';
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (int $weekday): array => ['value' => $weekday, 'label' => self::label($weekday)],
            self::ordered(),
        );
    }
}
