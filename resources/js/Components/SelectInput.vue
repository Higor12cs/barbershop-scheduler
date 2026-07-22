<script setup>
defineProps({
    modelValue: { type: [String, Number, null], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: null },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :value="modelValue"
        class="form-control"
        @change="$emit('update:modelValue', $event.target.value)"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">
            {{ option.label }}
        </option>
    </select>
</template>
