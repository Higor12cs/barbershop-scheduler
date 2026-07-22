<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: { type: Array, default: () => [] },
});
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, index) in links" :key="index">
            <span
                v-if="!link.url"
                class="rounded-lg px-3 py-2 text-sm text-muted"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="rounded-lg border px-3 py-2 text-sm transition-colors"
                :class="
                    link.active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-border bg-surface text-secondary hover:bg-surface-muted'
                "
                v-html="link.label"
            />
        </template>
    </nav>
</template>
