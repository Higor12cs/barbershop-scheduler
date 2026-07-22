<?php

namespace App\Enums;

enum NotificationType: string
{
    case Booking = 'booking';
    case Reminder = 'reminder';
    case Confirmation = 'confirmation';

    public function label(): string
    {
        return match ($this) {
            self::Booking => 'Agendamento',
            self::Reminder => 'Lembrete',
            self::Confirmation => 'Confirmação',
        };
    }
}
