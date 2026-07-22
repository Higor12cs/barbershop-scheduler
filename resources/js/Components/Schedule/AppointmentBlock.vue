<script setup>
import { computed } from 'vue';

const props = defineProps({
    appointment: { type: Object, required: true },
    draggable: { type: Boolean, default: false },
    dragging: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const statusClass = computed(() => `appt-${props.appointment.status.replace('_', '-')}`);
</script>

<template>
    <div
        class="appt-block"
        :class="[statusClass, draggable ? 'appt-draggable' : 'cursor-pointer', { 'appt-dragging': dragging }]"
    >
        <div class="flex items-center gap-1 font-semibold">
            <span>{{ appointment.start_time }}</span>
            <span v-if="!compact" class="opacity-70">–{{ appointment.end_time }}</span>
        </div>
        <span class="truncate font-medium">{{ appointment.customer_name }}</span>
        <span v-if="!compact" class="truncate opacity-80">{{ appointment.product_name }}</span>
    </div>
</template>
