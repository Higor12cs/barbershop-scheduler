<?php

namespace App\Support;

class Permissions
{
    public const GUARD = 'web';

    public const ROLE_ADMIN = 'Administrador';

    private const MODULES = [
        'appointments' => [
            'label' => 'Agenda',
            'permissions' => [
                'appointments.view' => ['label' => 'Visualizar', 'routes' => ['appointments.index']],
                'appointments.create' => ['label' => 'Criar', 'routes' => ['appointments.store']],
                'appointments.update' => ['label' => 'Editar', 'routes' => ['appointments.update', 'appointments.reschedule', 'appointments.status']],
                'appointments.delete' => ['label' => 'Excluir', 'routes' => ['appointments.destroy']],
            ],
        ],
        'customers' => [
            'label' => 'Clientes',
            'permissions' => [
                'customers.view' => ['label' => 'Visualizar', 'routes' => ['customers.index', 'customers.show']],
                'customers.create' => ['label' => 'Criar', 'routes' => ['customers.create', 'customers.store', 'customers.quick-store']],
                'customers.update' => ['label' => 'Editar', 'routes' => ['customers.edit', 'customers.update', 'customers.quick-update']],
                'customers.delete' => ['label' => 'Excluir', 'routes' => ['customers.destroy']],
            ],
        ],
        'employees' => [
            'label' => 'Funcionários',
            'permissions' => [
                'employees.view' => ['label' => 'Visualizar', 'routes' => ['employees.index', 'employees.show']],
                'employees.create' => ['label' => 'Criar', 'routes' => ['employees.create', 'employees.store']],
                'employees.update' => ['label' => 'Editar', 'routes' => ['employees.edit', 'employees.update']],
                'employees.delete' => ['label' => 'Excluir', 'routes' => ['employees.destroy']],
            ],
        ],
        'products' => [
            'label' => 'Produtos',
            'permissions' => [
                'products.view' => ['label' => 'Visualizar', 'routes' => ['products.index', 'products.show']],
                'products.create' => ['label' => 'Criar', 'routes' => ['products.create', 'products.store']],
                'products.update' => ['label' => 'Editar', 'routes' => ['products.edit', 'products.update']],
                'products.delete' => ['label' => 'Excluir', 'routes' => ['products.destroy']],
            ],
        ],
        'recurrences' => [
            'label' => 'Recorrências',
            'permissions' => [
                'recurrences.view' => ['label' => 'Visualizar', 'routes' => ['recurrences.index', 'settings.recurrence.index']],
                'recurrences.create' => ['label' => 'Criar', 'routes' => ['recurrences.create', 'recurrences.store']],
                'recurrences.update' => ['label' => 'Editar', 'routes' => ['recurrences.edit', 'recurrences.update', 'settings.recurrence.update']],
                'recurrences.delete' => ['label' => 'Excluir', 'routes' => ['recurrences.destroy']],
            ],
        ],
        'sales' => [
            'label' => 'Vendas',
            'permissions' => [
                'sales.view' => ['label' => 'Visualizar', 'routes' => ['sales.index', 'sales.show']],
                'sales.create' => ['label' => 'Criar', 'routes' => ['sales.create', 'sales.store']],
                'sales.delete' => ['label' => 'Excluir', 'routes' => ['sales.destroy']],
            ],
        ],
        'reports' => [
            'label' => 'Relatórios',
            'permissions' => [
                'reports.view' => ['label' => 'Visualizar', 'routes' => ['reports.*']],
            ],
        ],
        'users' => [
            'label' => 'Usuários',
            'permissions' => [
                'users.view' => ['label' => 'Visualizar', 'routes' => ['settings.users.index']],
                'users.create' => ['label' => 'Criar', 'routes' => ['settings.users.create', 'settings.users.store', 'settings.users.check-email']],
                'users.update' => ['label' => 'Editar', 'routes' => ['settings.users.edit', 'settings.users.update']],
                'users.delete' => ['label' => 'Excluir', 'routes' => ['settings.users.destroy']],
            ],
        ],
        'roles' => [
            'label' => 'Papéis',
            'permissions' => [
                'roles.view' => ['label' => 'Visualizar', 'routes' => ['settings.roles.index']],
                'roles.create' => ['label' => 'Criar', 'routes' => ['settings.roles.create', 'settings.roles.store']],
                'roles.update' => ['label' => 'Editar', 'routes' => ['settings.roles.edit', 'settings.roles.update']],
                'roles.delete' => ['label' => 'Excluir', 'routes' => ['settings.roles.destroy']],
            ],
        ],
        'notifications' => [
            'label' => 'Notificações',
            'permissions' => [
                'notifications.view' => ['label' => 'Visualizar', 'routes' => ['settings.messages.index', 'settings.messages.show']],
                'notifications.update' => ['label' => 'Editar', 'routes' => ['settings.messages.update']],
            ],
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'permissions' => [
                'whatsapp.view' => ['label' => 'Visualizar', 'routes' => ['settings.whatsapp.index', 'settings.whatsapp.status']],
                'whatsapp.update' => ['label' => 'Gerenciar', 'routes' => ['settings.whatsapp.restart', 'settings.whatsapp.test']],
            ],
        ],
    ];

    private static ?array $routeMap = null;

    public static function all(): array
    {
        $permissions = [];

        foreach (self::MODULES as $config) {
            foreach (array_keys($config['permissions']) as $permission) {
                $permissions[] = $permission;
            }
        }

        return $permissions;
    }

    public static function forRoute(?string $name): ?string
    {
        if ($name === null) {
            return null;
        }

        $map = self::routeMap();

        if (array_key_exists($name, $map['exact'])) {
            return $map['exact'][$name];
        }

        foreach ($map['wildcards'] as $prefix => $permission) {
            if (str_starts_with($name, $prefix)) {
                return $permission;
            }
        }

        return null;
    }

    public static function grouped(): array
    {
        $groups = [];

        foreach (self::MODULES as $key => $config) {
            $permissions = [];

            foreach ($config['permissions'] as $name => $meta) {
                $permissions[] = ['name' => $name, 'label' => $meta['label']];
            }

            $groups[] = [
                'key' => $key,
                'label' => $config['label'],
                'permissions' => $permissions,
            ];
        }

        return $groups;
    }

    private static function routeMap(): array
    {
        if (self::$routeMap !== null) {
            return self::$routeMap;
        }

        $exact = [];
        $wildcards = [];

        foreach (self::MODULES as $config) {
            foreach ($config['permissions'] as $permission => $meta) {
                foreach ($meta['routes'] as $route) {
                    if (str_ends_with($route, '*')) {
                        $wildcards[rtrim($route, '*')] = $permission;
                    } else {
                        $exact[$route] = $permission;
                    }
                }
            }
        }

        return self::$routeMap = ['exact' => $exact, 'wildcards' => $wildcards];
    }
}
