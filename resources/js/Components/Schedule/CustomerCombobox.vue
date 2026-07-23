<script setup>
import { computed, nextTick, ref, watch } from "vue";
import Icon from "../Icon.vue";
import { birthdayProximity, formatDayMonth } from "../../Support/date.js";

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    customers: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue", "create", "edit"]);

const query = ref("");
const open = ref(false);
const highlighted = ref(0);

const selected = computed(
    () =>
        props.customers.find((customer) => customer.id === props.modelValue) ||
        null,
);

watch(
    () => props.modelValue,
    () => {
        if (selected.value) {
            query.value = selected.value.name;
        }
    },
    { immediate: true },
);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (
        term === "" ||
        (selected.value && selected.value.name === query.value)
    ) {
        return props.customers.slice(0, 8);
    }

    return props.customers
        .filter(
            (customer) =>
                customer.name.toLowerCase().includes(term) ||
                (customer.phone || "").includes(term),
        )
        .slice(0, 8);
});

const birthdayLabels = {
    today: "Hoje",
    week: "Esta semana",
    month: "Este mês",
};

function birthday(customer) {
    const proximity = birthdayProximity(customer.birth_date);

    if (proximity === null) {
        return null;
    }

    return {
        label: birthdayLabels[proximity],
        class: proximity === "month" ? "text-muted" : "text-amber-600",
    };
}

const selectedBirthday = computed(() => {
    if (!selected.value?.birth_date) {
        return null;
    }

    const proximity = birthdayProximity(selected.value.birth_date);

    if (proximity === null) {
        return null;
    }

    return {
        date: formatDayMonth(selected.value.birth_date),
        label: birthdayLabels[proximity],
    };
});

function onInput(event) {
    query.value = event.target.value;
    open.value = true;
    highlighted.value = 0;

    if (props.modelValue !== null) {
        emit("update:modelValue", null);
    }
}

function select(customer) {
    emit("update:modelValue", customer.id);
    query.value = customer.name;
    open.value = false;
}

function onFocus() {
    open.value = true;
    highlighted.value = 0;
}

function close() {
    nextTick(() => {
        open.value = false;

        if (selected.value) {
            query.value = selected.value.name;
        }
    });
}

function onCreate() {
    open.value = false;
    emit("create", query.value.trim());
}

function onKeydown(event) {
    if (!open.value) {
        return;
    }

    if (event.key === "ArrowDown") {
        event.preventDefault();
        highlighted.value = Math.min(
            highlighted.value + 1,
            filtered.value.length - 1,
        );
    } else if (event.key === "ArrowUp") {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === "Enter" && filtered.value[highlighted.value]) {
        event.preventDefault();
        select(filtered.value[highlighted.value]);
    } else if (event.key === "Escape") {
        open.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Icon
                name="user"
                class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"
            />
            <input
                :value="query"
                type="text"
                class="form-control pl-9"
                :class="selected ? 'pr-10' : ''"
                placeholder="Buscar..."
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
                @blur="close"
                @keydown="onKeydown"
            />
            <button
                v-if="selected"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
                title="Editar cliente"
                @click="emit('edit', selected)"
            >
                <Icon name="pencil" class="size-4" />
            </button>
        </div>

        <div
            v-if="selectedBirthday && !open"
            class="mt-1.5 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
        >
            <Icon name="cake" class="size-3.5 shrink-0" />
            <span
                >Aniversário em {{ selectedBirthday.date }} ·
                {{ selectedBirthday.label }}</span
            >
        </div>

        <div
            v-if="open"
            class="dropdown-panel absolute z-20 mt-1 max-h-60 w-full space-y-0.5 overflow-auto p-1.5"
        >
            <button
                v-for="(customer, index) in filtered"
                :key="customer.id"
                type="button"
                class="dropdown-item"
                :class="{ 'dropdown-item-active': index === highlighted }"
                @mousedown.prevent="select(customer)"
            >
                <span class="flex-1 truncate">{{ customer.name }}</span>
                <span
                    v-if="birthday(customer)"
                    class="flex items-center gap-1 text-xs"
                    :class="birthday(customer).class"
                >
                    <Icon name="cake" class="size-3.5" />
                    {{ birthday(customer).label }}
                </span>
                <span v-if="customer.phone" class="text-xs text-muted">{{
                    customer.phone
                }}</span>
            </button>

            <p
                v-if="filtered.length === 0"
                class="px-3 py-2 text-sm text-muted"
            >
                Nenhum cliente encontrado.
            </p>

            <button
                type="button"
                class="dropdown-item border-t border-border text-primary hover:text-primary"
                @mousedown.prevent="onCreate"
            >
                <Icon name="plus" class="size-4 shrink-0" />
                <span class="truncate">{{
                    query.trim()
                        ? `Cadastrar "${query.trim()}"`
                        : "Novo cliente"
                }}</span>
            </button>
        </div>
    </div>
</template>
