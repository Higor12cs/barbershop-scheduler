<script setup>
defineProps({
    label: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
});
</script>

<template>
    <div class="form-group">
        <label v-if="label" class="form-label">{{ label }}</label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-secondary">{{ hint }}</p>
        <p v-if="error" class="form-error">{{ error }}</p>
    </div>
</template>
