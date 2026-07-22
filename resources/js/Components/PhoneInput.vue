<script setup>
import { ref, watch } from 'vue';
import { Mask } from 'maska';
import { vMaska } from 'maska/vue';

const props = defineProps({
    modelValue: { type: [String, null], default: '' },
    placeholder: { type: String, default: '(11) 98765-4321' },
});

const emit = defineEmits(['update:modelValue']);

const phoneMasks = ['(##) ####-####', '(##) #####-####'];
const mask = new Mask({ mask: phoneMasks });

const display = ref(props.modelValue ? mask.masked(String(props.modelValue)) : '');
let lastEmitted = props.modelValue ?? '';

const maskOptions = {
    mask: phoneMasks,
    onMaska: (detail) => {
        display.value = detail.masked;
        lastEmitted = detail.unmasked;
        emit('update:modelValue', detail.unmasked);
    },
};

watch(
    () => props.modelValue,
    (value) => {
        const next = value ?? '';

        if (next !== lastEmitted) {
            lastEmitted = next;
            display.value = next ? mask.masked(String(next)) : '';
        }
    },
);
</script>

<template>
    <input
        v-maska="maskOptions"
        :value="display"
        type="tel"
        class="form-control"
        :placeholder="placeholder"
    >
</template>
