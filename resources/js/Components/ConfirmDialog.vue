<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';
import Modal from './Modal.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmar Ação' },
    message: { type: String, default: 'Tem certeza que deseja continuar?' },
    confirmLabel: { type: String, default: 'Confirmar' },
    cancelLabel: { type: String, default: 'Cancelar' },
    processing: { type: Boolean, default: false },
    variant: { type: String, default: 'danger' },
});

const emit = defineEmits(['confirm', 'cancel']);

const isDanger = computed(() => props.variant !== 'primary');
</script>

<template>
    <Modal :show="show" :title="title" max-width="sm" @close="emit('cancel')">
        <div class="flex items-start gap-3">
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-full"
                :class="isDanger ? 'bg-danger/10 text-danger' : 'bg-primary/10 text-primary'"
            >
                <Icon :name="isDanger ? 'alert-triangle' : 'check-circle'" class="size-5" />
            </span>
            <p class="text-sm text-secondary">{{ message }}</p>
        </div>

        <template #footer>
            <button type="button" class="btn btn-secondary" :disabled="processing" @click="emit('cancel')">
                {{ cancelLabel }}
            </button>
            <button
                type="button"
                class="btn"
                :class="isDanger ? 'btn-danger' : 'btn-primary'"
                :disabled="processing"
                @click="emit('confirm')"
            >
                {{ confirmLabel }}
            </button>
        </template>
    </Modal>
</template>
