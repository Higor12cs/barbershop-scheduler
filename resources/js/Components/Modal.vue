<script setup>
import { onBeforeUnmount, onMounted, watch } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: null },
    maxWidth: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const widths = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
};

function close() {
    if (props.closeable) {
        emit('close');
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && props.show) {
        close();
    }
}

watch(
    () => props.show,
    (value) => {
        document.body.style.overflow = value ? 'hidden' : '';
    },
);

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="glass-overlay absolute inset-0" @click="close" />

            <Transition
                appear
                enter-active-class="transition-opacity duration-150"
                enter-from-class="opacity-0"
            >
                <div class="card relative w-full" :class="widths[maxWidth]">
                    <div v-if="title || closeable" class="card-header flex items-center justify-between">
                        <h2 class="card-title">{{ title }}</h2>
                        <button v-if="closeable" type="button" class="toast-close" @click="close">
                            <Icon name="x" class="size-5" />
                        </button>
                    </div>

                    <div class="card-body">
                        <slot />
                    </div>

                    <div v-if="$slots.footer" class="flex justify-end gap-2 border-t border-border px-5 py-4">
                        <slot name="footer" />
                    </div>
                </div>
            </Transition>
        </div>
    </Teleport>
</template>
