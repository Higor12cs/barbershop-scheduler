<script setup>
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';

const page = usePage();
const toasts = ref([]);
let counter = 0;

const icons = {
    success: 'check-circle',
    error: 'alert-triangle',
    warning: 'alert-triangle',
    info: 'info',
};

function push(type, message) {
    if (!message) {
        return;
    }

    const id = ++counter;
    toasts.value.push({ id, type, message });

    setTimeout(() => dismiss(id), 5000);
}

function dismiss(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

function consumeFlash() {
    const flash = page.props.flash || {};

    push('success', flash.success);
    push('error', flash.error);
    push('warning', flash.warning);
    push('info', flash.info);
}

onMounted(consumeFlash);

watch(() => page.props.flash, consumeFlash);
</script>

<template>
    <Teleport to="body">
        <div class="toast-host">
            <TransitionGroup name="toast">
                <div v-for="toast in toasts" :key="toast.id" class="toast" :class="`toast-${toast.type}`">
                    <Icon :name="icons[toast.type]" class="size-5 shrink-0" />
                    <span class="toast-message">{{ toast.message }}</span>
                    <button type="button" class="toast-close" @click="dismiss(toast.id)">
                        <Icon name="x" class="size-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
