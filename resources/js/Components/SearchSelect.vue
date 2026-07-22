<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    modelValue: { type: [Number, String, null], default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Buscar...' },
    icon: { type: String, default: 'search' },
});

const emit = defineEmits(['update:modelValue']);

const query = ref('');
const open = ref(false);
const highlighted = ref(0);

const selected = computed(() => props.options.find((option) => option.value === props.modelValue) || null);

watch(
    () => [props.modelValue, props.options],
    () => {
        query.value = selected.value ? selected.value.label : '';
    },
    { immediate: true, deep: true },
);

const filtered = computed(() => {
    const term = query.value.trim().toLowerCase();

    if (term === '' || (selected.value && selected.value.label === query.value)) {
        return props.options.slice(0, 8);
    }

    return props.options.filter((option) => option.label.toLowerCase().includes(term)).slice(0, 8);
});

function onInput(event) {
    query.value = event.target.value;
    open.value = true;
    highlighted.value = 0;

    if (props.modelValue !== null && props.modelValue !== '') {
        emit('update:modelValue', null);
    }
}

function select(option) {
    emit('update:modelValue', option.value);
    query.value = option.label;
    open.value = false;
}

function clear() {
    emit('update:modelValue', null);
    query.value = '';
    open.value = false;
}

function onFocus() {
    open.value = true;
    highlighted.value = 0;
}

function close() {
    nextTick(() => {
        open.value = false;
        query.value = selected.value ? selected.value.label : '';
    });
}

function onKeydown(event) {
    if (!open.value) {
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlighted.value = Math.min(highlighted.value + 1, filtered.value.length - 1);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlighted.value = Math.max(highlighted.value - 1, 0);
    } else if (event.key === 'Enter' && filtered.value[highlighted.value]) {
        event.preventDefault();
        select(filtered.value[highlighted.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
    }
}
</script>

<template>
    <div class="relative">
        <div class="relative">
            <Icon :name="icon" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
            <input
                :value="query"
                type="text"
                class="form-control pl-9"
                :class="selected ? 'pr-10' : ''"
                :placeholder="placeholder"
                autocomplete="off"
                @input="onInput"
                @focus="onFocus"
                @blur="close"
                @keydown="onKeydown"
            >
            <button
                v-if="selected"
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-md p-1 text-muted transition-colors hover:bg-surface-muted hover:text-foreground"
                title="Limpar"
                @click="clear"
            >
                <Icon name="x" class="size-4" />
            </button>
        </div>

        <div v-if="open && filtered.length" class="dropdown-panel absolute z-20 mt-1 max-h-60 w-full space-y-0.5 overflow-auto p-1.5">
            <button
                v-for="(option, index) in filtered"
                :key="option.value"
                type="button"
                class="dropdown-item"
                :class="{ 'dropdown-item-active': index === highlighted }"
                @mousedown.prevent="select(option)"
            >
                <span class="flex-1 truncate">{{ option.label }}</span>
            </button>
        </div>

        <div v-else-if="open && query.trim() !== ''" class="dropdown-panel absolute z-20 mt-1 w-full p-1.5 text-sm text-muted">
            <p class="px-3 py-2">Nenhum resultado.</p>
        </div>
    </div>
</template>
