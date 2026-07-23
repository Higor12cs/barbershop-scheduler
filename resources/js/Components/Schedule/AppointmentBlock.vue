<script setup>
import { computed } from 'vue';

const props = defineProps({
    appointment: { type: Object, required: true },
    draggable: { type: Boolean, default: false },
    dragging: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const statusClass = computed(() => `appt-${props.appointment.status.replace('_', '-')}`);

/** Cards too short for the full range fall back to the start time alone. */
const timeLabel = computed(() =>
    props.compact
        ? props.appointment.start_time
        : `${props.appointment.start_time} – ${props.appointment.end_time}`,
);
</script>

<template>
    <div
        class="appt-block"
        :class="[
            statusClass,
            compact ? 'py-0.5' : 'py-2',
            draggable ? 'appt-draggable' : 'cursor-pointer',
            { 'appt-dragging': dragging },
        ]"
    >
        <div class="flex items-start justify-between gap-1.5">
            <span class="truncate font-semibold">{{ appointment.customer_name }}</span>
            <span class="shrink-0 font-medium tabular-nums opacity-70">{{ timeLabel }}</span>
        </div>
        <span v-if="!compact" class="truncate opacity-80">{{ appointment.product_name }}</span>
    </div>
</template>
