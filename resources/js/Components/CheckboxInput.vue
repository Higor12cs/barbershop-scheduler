<script setup>
defineProps({
    modelValue: { type: [Boolean, Array], default: false },
    value: { type: [String, Number, Boolean], default: null },
    label: { type: String, default: null },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="flex items-center gap-2 text-sm text-foreground">
        <input
            type="checkbox"
            class="form-checkbox"
            :value="value"
            :checked="Array.isArray(modelValue) ? modelValue.includes(value) : modelValue"
            @change="
                Array.isArray(modelValue)
                    ? $emit(
                          'update:modelValue',
                          $event.target.checked
                              ? [...modelValue, value]
                              : modelValue.filter((item) => item !== value),
                      )
                    : $emit('update:modelValue', $event.target.checked)
            "
        >
        <span v-if="label">{{ label }}</span>
        <slot />
    </label>
</template>
