<script setup>
import Icon from './Icon.vue';

defineProps({
    icon: { type: String, default: 'search' },
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center">
        <span class="flex size-12 items-center justify-center rounded-full bg-surface-muted text-secondary">
            <Icon :name="icon" class="size-6" />
        </span>
        <div class="space-y-1">
            <p class="text-base font-semibold text-foreground">{{ title }}</p>
            <p v-if="description" class="text-sm text-secondary">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>
