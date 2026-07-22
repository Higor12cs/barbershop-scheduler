<?php

namespace App\Support;

class EmployeeColors
{
    private const COLORS = [
        ['value' => '#71717a', 'label' => 'Cinza', 'class' => 'bg-zinc-500'],
        ['value' => '#059669', 'label' => 'Esmeralda', 'class' => 'bg-emerald-600'],
        ['value' => '#0284c7', 'label' => 'Azul', 'class' => 'bg-sky-600'],
        ['value' => '#d97706', 'label' => 'Âmbar', 'class' => 'bg-amber-600'],
        ['value' => '#e11d48', 'label' => 'Rosa', 'class' => 'bg-rose-600'],
        ['value' => '#7c3aed', 'label' => 'Violeta', 'class' => 'bg-violet-600'],
        ['value' => '#0d9488', 'label' => 'Verde-azulado', 'class' => 'bg-teal-600'],
    ];

    public static function options(): array
    {
        return self::COLORS;
    }

    public static function values(): array
    {
        return array_map(fn (array $color): string => $color['value'], self::COLORS);
    }

    public static function default(): string
    {
        return self::COLORS[0]['value'];
    }
}
