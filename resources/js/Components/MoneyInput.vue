<script setup>
import { ref, watch } from 'vue';
import { vMaska } from 'maska/vue';

const props = defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    placeholder: { type: String, default: '0,00' },
});

const emit = defineEmits(['update:modelValue']);

const formatter = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

function format(value) {
    if (value === '' || value === null || value === undefined || Number.isNaN(Number(value))) {
        return '';
    }

    return formatter.format(Number(value));
}

const display = ref(format(props.modelValue));
let lastEmitted = props.modelValue === null || props.modelValue === '' ? '' : String(props.modelValue);

const maskOptions = {
    number: { locale: 'pt-BR', fraction: 2, unsigned: true },
    onMaska: (detail) => {
        display.value = detail.masked;
        lastEmitted = detail.unmasked;
        emit('update:modelValue', detail.unmasked);
    },
};

watch(
    () => props.modelValue,
    (value) => {
        const next = value === null || value === undefined || value === '' ? '' : String(value);

        if (next !== lastEmitted) {
            lastEmitted = next;
            display.value = format(value);
        }
    },
);
</script>

<template>
    <input
        v-maska="maskOptions"
        :value="display"
        type="text"
        inputmode="decimal"
        class="form-control"
        :placeholder="placeholder"
    >
</template>
