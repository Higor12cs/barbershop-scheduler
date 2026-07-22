<?php

namespace App\Enums;

enum WhatsAppProvider: string
{
    case DiChat = 'dichat';
    case Log = 'log';

    public function label(): string
    {
        return match ($this) {
            self::DiChat => 'DiChat',
            self::Log => 'Log',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $provider): array => ['value' => $provider->value, 'label' => $provider->label()],
            self::cases(),
        );
    }
}
