<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";
import Dropdown from "../Components/Dropdown.vue";
import Icon from "../Components/Icon.vue";
import ToastHost from "../Components/ToastHost.vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const appName = computed(() => page.props.appName);

const navItems = [
    { label: "Painel", icon: "home", route: "admin.dashboard" },
    { label: "Ambientes", icon: "building", route: "admin.tenants.index" },
];

function isActive(name) {
    return (
        route().current(name) ||
        route().current(`${name.replace(".index", "")}.*`)
    );
}

function logout() {
    router.post(route("logout"));
}
</script>

<template>
    <div class="min-h-screen">
        <ToastHost />

        <div class="sticky top-0 z-40 bg-surface-alt px-3 pt-3 lg:px-6 lg:pt-6">
            <header
                class="mx-auto w-full max-w-6xl rounded-2xl border border-border bg-surface"
            >
                <div
                    class="flex h-16 items-center justify-between gap-4 px-4 lg:px-6"
                >
                    <div class="flex items-center gap-6">
                        <Link
                            :href="route('admin.dashboard')"
                            class="flex items-center gap-2 font-semibold"
                        >
                            <span
                                class="flex size-9 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                            >
                                <Icon name="shield" class="size-5" />
                            </span>
                            <span class="leading-tight">
                                {{ appName }}
                                <span class="badge badge-info ml-1 align-middle"
                                    >Admin</span
                                >
                            </span>
                        </Link>

                        <nav class="hidden items-center gap-1 sm:flex">
                            <Link
                                v-for="item in navItems"
                                :key="item.route"
                                :href="route(item.route)"
                                class="nav-link"
                                :class="{
                                    'nav-link-active': isActive(item.route),
                                }"
                            >
                                <Icon
                                    :name="item.icon"
                                    class="size-5 shrink-0"
                                />
                                <span>{{ item.label }}</span>
                            </Link>
                        </nav>
                    </div>

                    <Dropdown align="right" width="w-56" direction="down">
                        <template #trigger="{ open }">
                            <button
                                type="button"
                                class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-surface-muted"
                            >
                                <span
                                    class="flex size-8 items-center justify-center rounded-full bg-surface-muted text-xs font-semibold text-secondary"
                                >
                                    {{ user?.name?.charAt(0)?.toUpperCase() }}
                                </span>
                                <div
                                    class="hidden min-w-0 leading-tight sm:block"
                                >
                                    <p class="truncate text-sm font-medium">
                                        {{ user?.name }}
                                    </p>
                                    <p class="truncate text-xs text-secondary">
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
                            Ir para Ambientes
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

                <nav
                    class="flex items-center gap-1 border-t border-border px-4 py-2 sm:hidden"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.route"
                        :href="route(item.route)"
                        class="nav-link flex-1 justify-center"
                        :class="{ 'nav-link-active': isActive(item.route) }"
                    >
                        <Icon :name="item.icon" class="size-5 shrink-0" />
                        <span>{{ item.label }}</span>
                    </Link>
                </nav>
            </header>
        </div>

        <main class="mx-auto w-full max-w-6xl px-4 pt-4 pb-8 lg:px-6 lg:pt-6">
            <slot />
        </main>
    </div>
</template>
