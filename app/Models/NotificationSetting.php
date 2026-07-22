<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    public const DEFAULT_BOOKING_TEMPLATE = 'Olá {cliente}! Seu agendamento de {servico} com {funcionario} foi marcado para {data} às {hora}. Adicione ao seu calendário: {link_calendario}';

    public const DEFAULT_REMINDER_TEMPLATE = 'Olá {cliente}! Passando para lembrar do seu {servico} hoje às {hora} com {funcionario}. Até logo!';

    public const DEFAULT_CONFIRMATION_TEMPLATE = 'Olá {cliente}! Você tem {servico} em {data} às {hora} com {funcionario}. Podemos confirmar sua presença?';

    protected $fillable = [
        'reminder_enabled',
        'reminder_minutes_before',
        'reminder_template',
        'confirmation_enabled',
        'confirmation_minutes_before',
        'confirmation_template',
        'booking_enabled',
        'booking_template',
        'recurrence_horizon_days',
    ];

    protected function casts(): array
    {
        return [
            'reminder_enabled' => 'boolean',
            'reminder_minutes_before' => 'integer',
            'confirmation_enabled' => 'boolean',
            'confirmation_minutes_before' => 'integer',
            'booking_enabled' => 'boolean',
            'recurrence_horizon_days' => 'integer',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'reminder_enabled' => false,
            'reminder_minutes_before' => 60,
            'reminder_template' => self::DEFAULT_REMINDER_TEMPLATE,
            'confirmation_enabled' => false,
            'confirmation_minutes_before' => 1440,
            'confirmation_template' => self::DEFAULT_CONFIRMATION_TEMPLATE,
            'booking_enabled' => false,
            'booking_template' => self::DEFAULT_BOOKING_TEMPLATE,
            'recurrence_horizon_days' => 30,
        ]);
    }
}
