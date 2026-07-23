<?php

namespace App\Support;

class Modules
{
    public const APPOINTMENTS = 'appointments';

    public const CUSTOMERS = 'customers';

    public const EMPLOYEES = 'employees';

    public const PRODUCTS = 'products';

    public const RECURRENCES = 'recurrences';

    public const SALES = 'sales';

    public const REPORTS = 'reports';

    public const WHATSAPP = 'whatsapp';

    private const MODULES = [
        self::APPOINTMENTS => 'Agenda',
        self::CUSTOMERS => 'Clientes',
        self::EMPLOYEES => 'Funcionários',
        self::PRODUCTS => 'Produtos',
        self::RECURRENCES => 'Recorrências',
        self::SALES => 'Vendas',
        self::REPORTS => 'Relatórios',
        self::WHATSAPP => 'WhatsApp',
    ];

    public static function all(): array
    {
        return array_keys(self::MODULES);
    }

    public static function defaults(): array
    {
        return self::all();
    }

    public static function label(string $key): ?string
    {
        return self::MODULES[$key] ?? null;
    }

    public static function isToggleable(string $key): bool
    {
        return array_key_exists($key, self::MODULES);
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::MODULES as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    public static function forRoute(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        return match (true) {
            str_starts_with($name, 'appointments.') => self::APPOINTMENTS,
            str_starts_with($name, 'blocks.') => self::APPOINTMENTS,
            str_starts_with($name, 'customers.') => self::CUSTOMERS,
            str_starts_with($name, 'employees.') => self::EMPLOYEES,
            str_starts_with($name, 'products.') => self::PRODUCTS,
            str_starts_with($name, 'recurrences.') => self::RECURRENCES,
            str_starts_with($name, 'settings.recurrence.') => self::RECURRENCES,
            str_starts_with($name, 'sales.') => self::SALES,
            str_starts_with($name, 'reports.') => self::REPORTS,
            str_starts_with($name, 'settings.whatsapp.') => self::WHATSAPP,
            default => null,
        };
    }
}
