<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import CommandPalette from "../Components/CommandPalette.vue";
import Dropdown from "../Components/Dropdown.vue";
import Icon from "../Components/Icon.vue";
import SidebarNav from "../Components/SidebarNav.vue";
import ToastHost from "../Components/ToastHost.vue";

const page = usePage();

const appName = computed(() => page.props.appName);
const tenant = computed(() => page.props.tenant);
const user = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions || []);
const modules = computed(() => tenant.value?.modules || []);

const drawerOpen = ref(false);

const navGroups = [
    {
        title: "Principal",
        items: [
            { label: "Painel", icon: "home", route: "dashboard.index" },
            {
                label: "Agenda",
                icon: "calendar",
                route: "appointments.index",
                module: "appointments",
            },
            {
                label: "Recorrências",
                icon: "repeat",
                route: "recurrences.index",
                module: "recurrences",
            },
        ],
    },
    {
        title: "Cadastros",
        items: [
            {
                label: "Clientes",
                icon: "users",
                route: "customers.index",
                module: "customers",
            },
            {
                label: "Funcionários",
                icon: "briefcase",
                route: "employees.index",
                module: "employees",
            },
            {
                label: "Produtos",
                icon: "package",
                route: "products.index",
                module: "products",
            },
        ],
    },
    {
        title: "Financeiro",
        items: [
            {
                label: "Vendas",
                icon: "shopping-cart",
                route: "sales.index",
                module: "sales",
            },
            {
                label: "Relatórios",
                icon: "chart-bar",
                route: "reports.index",
                module: "reports",
            },
        ],
    },
    {
        title: "Configurações",
        items: [
            {
                label: "WhatsApp",
                icon: "message-circle",
                route: "settings.whatsapp.index",
                module: "whatsapp",
            },
            {
                label: "Mensagens",
                icon: "settings",
                route: "settings.messages.index",
                permission: "notifications.view",
            },
            {
                label: "Usuários",
                icon: "shield",
                route: "settings.users.index",
                permission: "users.view",
            },
            {
                label: "Papéis",
                icon: "key",
                route: "settings.roles.index",
                permission: "roles.view",
            },
        ],
    },
];

function hasRoute(name) {
    try {
        return route().has(name);
    } catch {
        return false;
    }
}

function isVisible(item) {
    if (item.module && !modules.value.includes(item.module)) {
        return false;
    }

    if (item.permission && !permissions.value.includes(item.permission)) {
        return false;
    }

    if (item.route && !hasRoute(item.route)) {
        return false;
    }

    return true;
}

function filterItem(item) {
    if (!isVisible(item)) {
        return null;
    }

    if (!item.children) {
        return item;
    }

    const children = item.children.filter(isVisible);

    return children.length > 0 ? { ...item, children } : null;
}

const visibleGroups = computed(() =>
    navGroups
        .map((group) => ({
            ...group,
            items: group.items.map(filterItem).filter(Boolean),
        }))
        .filter((group) => group.items.length > 0),
);

const commandModules = computed(() =>
    visibleGroups.value.flatMap((group) =>
        group.items.flatMap((item) =>
            item.route
                ? [{ label: item.label, icon: item.icon, route: item.route }]
                : (item.children || []).map((child) => ({
                      label: child.label,
                      icon: child.icon ?? item.icon,
                      route: child.route,
                  })),
        ),
    ),
);

function logout() {
    router.post(route("logout"));
}
</script>

<template>
    <div class="min-h-screen">
        <ToastHost />
        <CommandPalette :modules="commandModules" />

        <div
            class="flex items-center justify-between gap-3 border-b border-border bg-surface px-4 py-3 lg:hidden"
        >
            <span class="flex items-center gap-2 font-semibold">
                <span
                    class="flex size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                >
                    <Icon name="calendar" class="size-4" />
                </span>
                {{ appName }}
            </span>
            <button
                type="button"
                class="btn btn-secondary px-2"
                @click="drawerOpen = true"
            >
                <Icon name="menu" class="size-5" />
            </button>
        </div>

        <div class="flex min-h-screen">
            <aside class="hidden shrink-0 p-3 lg:block">
                <div
                    class="sticky top-3 flex h-[calc(100vh-1.5rem)] w-64 flex-col rounded-2xl border border-border bg-surface p-3"
                >
                    <div class="flex items-center gap-2 px-2 py-2">
                        <span
                            class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                        >
                            <Icon name="calendar" class="size-5" />
                        </span>
                        <div class="leading-tight">
                            <p class="text-sm font-semibold">{{ appName }}</p>
                            <p class="truncate text-xs text-secondary">
                                {{ tenant?.name }}
                            </p>
                        </div>
                    </div>

                    <SidebarNav :groups="visibleGroups" class="mt-2" />

                    <div class="mt-3 border-t border-border pt-3">
                        <Dropdown align="left" width="w-full" direction="auto">
                            <template #trigger="{ open }">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left transition-colors hover:bg-surface-muted"
                                >
                                    <span
                                        class="flex size-8 items-center justify-center rounded-full bg-surface-muted text-xs font-semibold text-secondary"
                                    >
                                        {{
                                            user?.name?.charAt(0)?.toUpperCase()
                                        }}
                                    </span>
                                    <div class="min-w-0 flex-1 leading-tight">
                                        <p class="truncate text-sm font-medium">
                                            {{ user?.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-secondary"
                                        >
                                            {{ user?.email }}
                                        </p>
                                    </div>
                                    <Icon
                                        name="chevron-down"
                                        class="size-4 shrink-0 text-muted transition-transform"
                                        :class="{ 'rotate-180': open }"
                                    />
                                </button>
                            </template>
                            <Link
                                :href="route('tenant-selection.index')"
                                class="dropdown-item"
                            >
                                <Icon name="building" class="size-4" />
                                Trocar de Ambiente
                            </Link>
                            <Link
                                :href="route('password.change')"
                                class="dropdown-item"
                            >
                                <Icon name="key" class="size-4" />
                                Alterar Senha
                            </Link>
                            <button
                                type="button"
                                class="dropdown-item"
                                @click="logout"
                            >
                                <Icon name="log-out" class="size-4" />
                                Sair
                            </button>
                        </Dropdown>
                    </div>
                </div>
            </aside>

            <main
                class="min-w-0 flex-1 overflow-hidden p-4 lg:py-8 lg:pr-8 lg:pl-3"
            >
                <div class="mx-auto w-full">
                    <slot />
                </div>
            </main>
        </div>

        <div v-if="drawerOpen" class="fixed inset-0 z-50 lg:hidden">
            <div
                class="glass-overlay absolute inset-0"
                @click="drawerOpen = false"
            />

            <Transition
                appear
                enter-active-class="transition-transform duration-200"
                enter-from-class="-translate-x-full"
            >
                <aside
                    class="absolute inset-y-0 left-0 flex w-72 max-w-[85%] flex-col border-r border-border bg-surface p-3"
                >
                    <div class="flex items-center justify-between px-2 py-2">
                        <span class="flex items-center gap-2 font-semibold">
                            <span
                                class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                            >
                                <Icon name="calendar" class="size-5" />
                            </span>
                            <span class="leading-tight">
                                <span class="block text-sm font-semibold">{{
                                    appName
                                }}</span>
                                <span
                                    class="block truncate text-xs text-secondary"
                                    >{{ tenant?.name }}</span
                                >
                            </span>
                        </span>
                        <button
                            type="button"
                            class="toast-close"
                            @click="drawerOpen = false"
                        >
                            <Icon name="x" class="size-5" />
                        </button>
                    </div>

                    <SidebarNav
                        :groups="visibleGroups"
                        class="mt-2"
                        @navigate="drawerOpen = false"
                    />

                    <div class="mt-3 border-t border-border pt-3">
                        <Dropdown align="left" width="w-full" direction="auto">
                            <template #trigger="{ open }">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left transition-colors hover:bg-surface-muted"
                                >
                                    <span
                                        class="flex size-8 items-center justify-center rounded-full bg-surface-muted text-xs font-semibold text-secondary"
                                    >
                                        {{
                                            user?.name?.charAt(0)?.toUpperCase()
                                        }}
                                    </span>
                                    <div class="min-w-0 flex-1 leading-tight">
                                        <p class="truncate text-sm font-medium">
                                            {{ user?.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-secondary"
                                        >
                                            {{ user?.email }}
                                        </p>
                                    </div>
                                    <Icon
                                        name="chevron-down"
                                        class="size-4 shrink-0 text-muted transition-transform"
                                        :class="{ 'rotate-180': open }"
                                    />
                                </button>
                            </template>
                            <Link
                                :href="route('tenant-selection.index')"
                                class="dropdown-item"
                            >
                                <Icon name="building" class="size-4" />
                                Trocar de Ambiente
                            </Link>
                            <Link
                                :href="route('password.change')"
                                class="dropdown-item"
                            >
                                <Icon name="key" class="size-4" />
                                Alterar Senha
                            </Link>
                            <button
                                type="button"
                                class="dropdown-item"
                                @click="logout"
                            >
                                <Icon name="log-out" class="size-4" />
                                Sair
                            </button>
                        </Dropdown>
                    </div>
                </aside>
            </Transition>
        </div>
    </div>
</template>
