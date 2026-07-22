<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '../Components/Icon.vue';
import ToastHost from '../Components/ToastHost.vue';

defineProps({
    title: { type: String, default: null },
    subtitle: { type: String, default: null },
});

const page = usePage();

const appName = computed(() => page.props.appName);
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-surface-alt px-4 py-10">
        <ToastHost />

        <div class="w-full max-w-md">
            <div class="mb-6 flex flex-col items-center gap-2 text-center">
                <span class="flex size-12 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                    <Icon name="calendar" class="size-6" />
                </span>
                <span class="text-lg font-semibold">{{ appName }}</span>
            </div>

            <div class="card">
                <div class="card-body p-6 sm:p-8">
                    <div v-if="title" class="mb-6 space-y-1 text-center">
                        <h1 class="text-xl font-semibold">{{ title }}</h1>
                        <p v-if="subtitle" class="text-sm text-secondary">{{ subtitle }}</p>
                    </div>

                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
