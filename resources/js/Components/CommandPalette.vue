<script setup>
import { router, useHttp } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import Icon from './Icon.vue';

const props = defineProps({
    modules: { type: Array, default: () => [] },
});

const open = ref(false);
const query = ref('');
const highlighted = ref(0);
const inputRef = ref(null);
const remote = ref({ customers: [], employees: [] });

const http = useHttp({ q: '' });
let debounce = null;

const moduleResults = computed(() => {
    const term = query.value.trim().toLowerCase();
    const list = term
        ? props.modules.filter((module) => module.label.toLowerCase().includes(term))
        : props.modules;

    return list.slice(0, 6);
});

const results = computed(() => {
    const items = [];

    moduleResults.value.forEach((module) => {
        items.push({ type: 'module', label: module.label, icon: module.icon, href: route(module.route) });
    });

    remote.value.customers.forEach((customer) => {
        items.push({
            type: 'customer',
            label: customer.name,
            hint: customer.phone,
            icon: 'user',
            href: route('customers.show', customer.id),
        });
    });

    remote.value.employees.forEach((employee) => {
        items.push({
            type: 'employee',
            label: employee.name,
            color: employee.color,
            icon: 'briefcase',
            href: route('employees.show', employee.id),
        });
    });

    return items;
});

const typeLabels = {
    module: 'Módulos',
    customer: 'Clientes',
    employee: 'Profissionais',
};

function isFirstOfType(index) {
    return index === 0 || results.value[index - 1].type !== results.value[index].type;
}

function toggle() {
    open.value = !open.value;

    if (open.value) {
        query.value = '';
        remote.value = { customers: [], employees: [] };
        highlighted.value = 0;
        nextTick(() => inputRef.value?.focus());
    }
}

function close() {
    open.value = false;
}

function select(item) {
    if (!item) {
        return;
    }

    open.value = false;
    router.visit(item.href);
}

watch(query, (value) => {
    highlighted.value = 0;
    clearTimeout(debounce);

    const term = value.trim();

    if (term === '') {
        remote.value = { customers: [], employees: [] };

        return;
    }

    debounce = setTimeout(() => {
        http.q = term;
        http.get(route('search.index'), {
            onSuccess: (response) => {
                const data = response?.data ?? response;
                remote.value = {
                    customers: data?.customers ?? [],
                    employees: data?.employees ?? [],
                };
            },
        });
    }, 200);
});

function onGlobalKey(event) {
    if ((event.ctrlKey || event.metaKey) && (event.key === 'k' || event.key === 'K')) {
        event.preventDefault();
        toggle();
    }
}

function onKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, results.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        select(results.value[highlighted.value]);
    } else if (event.key === 'Escape') {
        close();
    }
}

onMounted(() => document.addEventListener('keydown', onGlobalKey));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onGlobalKey);
    clearTimeout(debounce);
});
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-24">
            <div class="glass-overlay absolute inset-0" @click="close" />

            <div class="card relative flex w-full max-w-xl flex-col overflow-hidden">
                <div class="flex items-center gap-3 border-b border-border px-4">
                    <Icon name="search" class="size-4 shrink-0 text-muted" />
                    <input
                        ref="inputRef"
                        v-model="query"
                        type="text"
                        class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted"
                        placeholder="Buscar módulos, clientes e profissionais..."
                        autocomplete="off"
                        @keydown="onKeydown"
                    >
                    <span class="hidden shrink-0 rounded-md border border-border px-1.5 py-0.5 text-xs text-muted sm:block">Esc</span>
                </div>

                <div v-if="results.length" class="max-h-80 overflow-auto p-1.5">
                    <template v-for="(item, index) in results" :key="`${item.type}-${index}`">
                        <p
                            v-if="isFirstOfType(index)"
                            class="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-muted uppercase"
                        >
                            {{ typeLabels[item.type] }}
                        </p>
                        <button
                            type="button"
                            class="dropdown-item"
                            :class="{ 'dropdown-item-active': index === highlighted }"
                            @mouseenter="highlighted = index"
                            @click="select(item)"
                        >
                            <span
                                v-if="item.type === 'employee'"
                                class="size-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: item.color }"
                            />
                            <Icon v-else :name="item.icon" class="size-4 shrink-0" />
                            <span class="flex-1 truncate text-left">{{ item.label }}</span>
                            <span v-if="item.hint" class="text-xs text-muted">{{ item.hint }}</span>
                        </button>
                    </template>
                </div>

                <div v-else class="px-4 py-10 text-center text-sm text-muted">
                    {{ query.trim() ? 'Nenhum resultado encontrado.' : 'Digite para buscar.' }}
                </div>
            </div>
        </div>
    </Teleport>
</template>
