<script setup>
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { route } from "ziggy-js";
import Icon from "./Icon.vue";

defineProps({
    groups: { type: Array, required: true },
});

const emit = defineEmits(["navigate"]);

const STORAGE_KEY = "agendapro.nav.submenus";

function loadOpen() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
    } catch {
        return {};
    }
}

const openSubmenus = ref(loadOpen());

function isActive(item) {
    return route().current(item.match ?? item.route);
}

function hrefFor(item) {
    return route(item.route, item.params ?? {});
}

function itemHasActiveChild(item) {
    return (item.children || []).some((child) => isActive(child));
}

function isSubmenuOpen(item) {
    return openSubmenus.value[item.label] === true || itemHasActiveChild(item);
}

function toggleSubmenu(item) {
    openSubmenus.value = {
        ...openSubmenus.value,
        [item.label]: !openSubmenus.value[item.label],
    };

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(openSubmenus.value));
    } catch {
        openSubmenus.value = { ...openSubmenus.value };
    }
}
</script>

<template>
    <nav class="flex flex-1 flex-col overflow-y-auto">
        <div
            v-for="group in groups"
            :key="group.title"
            class="flex flex-col gap-1"
        >
            <p
                class="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-muted uppercase"
            >
                {{ group.title }}
            </p>

            <template v-for="item in group.items" :key="item.label">
                <Link
                    v-if="item.route"
                    :href="hrefFor(item)"
                    class="nav-link"
                    :class="{ 'nav-link-active': isActive(item) }"
                    @click="emit('navigate')"
                >
                    <Icon :name="item.icon" class="size-5 shrink-0" />
                    <span>{{ item.label }}</span>
                </Link>

                <div v-else class="flex flex-col">
                    <button
                        type="button"
                        class="nav-link justify-between"
                        :class="{ 'text-foreground': itemHasActiveChild(item) }"
                        @click="toggleSubmenu(item)"
                    >
                        <span class="flex items-center gap-3">
                            <Icon :name="item.icon" class="size-5 shrink-0" />
                            <span>{{ item.label }}</span>
                        </span>
                        <Icon
                            name="chevron-down"
                            class="size-4 shrink-0 text-muted transition-transform"
                            :class="{ '-rotate-90': !isSubmenuOpen(item) }"
                        />
                    </button>

                    <div
                        v-show="isSubmenuOpen(item)"
                        class="mt-1 flex flex-col gap-1 pl-4"
                    >
                        <Link
                            v-for="child in item.children"
                            :key="child.route"
                            :href="hrefFor(child)"
                            class="nav-link"
                            :class="{
                                'nav-link-active': isActive(child),
                            }"
                            @click="emit('navigate')"
                        >
                            <Icon
                                v-if="child.icon"
                                :name="child.icon"
                                class="size-5 shrink-0"
                            />
                            <span>{{ child.label }}</span>
                        </Link>
                    </div>
                </div>
            </template>
        </div>
    </nav>
</template>
