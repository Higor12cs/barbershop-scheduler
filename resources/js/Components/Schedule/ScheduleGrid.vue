<script setup>
import { computed, onMounted, ref } from 'vue';
import AppointmentBlock from './AppointmentBlock.vue';
import { minutesToTime } from '../../Support/date.js';

const props = defineProps({
    columns: { type: Array, required: true },
    appointments: { type: Array, default: () => [] },
    settings: { type: Object, required: true },
    nowLine: { type: Object, default: () => ({ minutes: null, columnKey: null }) },
});

const emit = defineEmits(['slot-click', 'appointment-click', 'reschedule']);

const HOUR_HEIGHT = 132;
const DRAG_THRESHOLD = 5;

const dayStart = computed(() => props.settings.start_hour * 60);
const dayEnd = computed(() => props.settings.end_hour * 60);
const totalMinutes = computed(() => dayEnd.value - dayStart.value);
const pxPerMinute = HOUR_HEIGHT / 60;
const slotPx = computed(() => props.settings.slot_minutes * pxPerMinute);
const totalHeight = computed(() => totalMinutes.value * pxPerMinute);

const hours = computed(() =>
    Array.from({ length: props.settings.end_hour - props.settings.start_hour + 1 }, (_, index) => ({
        label: minutesToTime((props.settings.start_hour + index) * 60),
        top: index * HOUR_HEIGHT,
    })),
);

const lineMinutes = computed(() => props.settings.line_minutes ?? 30);
const linePx = computed(() => lineMinutes.value * pxPerMinute);
const lineCount = computed(() => Math.round(totalMinutes.value / lineMinutes.value));

const gridStyle = computed(() => ({
    gridTemplateColumns: `64px repeat(${props.columns.length}, minmax(150px, 1fr))`,
}));

const dragging = ref(null);
const scroller = ref(null);

onMounted(() => {
    if (props.nowLine.minutes === null) {
        return;
    }

    scroller.value?.scrollTo({ top: Math.max(nowLineTop() - HOUR_HEIGHT, 0) });
});

function isDraggable(appointment) {
    return ['scheduled', 'confirmed'].includes(appointment.status);
}

function effective(appointment) {
    if (dragging.value && dragging.value.id === appointment.id) {
        return { columnKey: dragging.value.columnKey, startMinutes: minutesFromTop(dragging.value.top) };
    }

    return { columnKey: appointment.columnKey, startMinutes: appointment.start_minutes };
}

function isDropTarget(columnKey) {
    return (
        dragging.value !== null &&
        dragging.value.targetKey === columnKey &&
        dragging.value.targetKey !== dragging.value.columnKey
    );
}

function blocksFor(columnKey) {
    return props.appointments.filter((appointment) => effective(appointment).columnKey === columnKey);
}

function blockStyle(appointment) {
    const { startMinutes } = effective(appointment);
    const top = (startMinutes - dayStart.value) * pxPerMinute;
    const height = Math.max(appointment.duration_minutes * pxPerMinute - 2, 18);

    return { top: `${top}px`, height: `${height}px`, left: '2px', right: '2px' };
}

function minutesFromTop(top) {
    const raw = top / pxPerMinute + dayStart.value;
    const snapped = Math.round(raw / props.settings.slot_minutes) * props.settings.slot_minutes;

    return Math.min(Math.max(snapped, dayStart.value), dayEnd.value - props.settings.slot_minutes);
}

function nowLineTop() {
    return (props.nowLine.minutes - dayStart.value) * pxPerMinute;
}

function showNowLine(columnKey) {
    if (props.nowLine.minutes === null) {
        return false;
    }

    if (props.nowLine.minutes < dayStart.value || props.nowLine.minutes > dayEnd.value) {
        return false;
    }

    return props.nowLine.columnKey === null || props.nowLine.columnKey === columnKey;
}

function onColumnClick(columnKey, event) {
    const rect = event.currentTarget.getBoundingClientRect();
    const y = event.clientY - rect.top;
    const minutes = minutesFromTop(y);

    emit('slot-click', { columnKey, startMinutes: minutes });
}

function columnKeyAtPoint(clientX, clientY) {
    const element = document.elementFromPoint(clientX, clientY)?.closest('[data-col-key]');

    return element ? element.dataset.colKey : null;
}

function onBlockPointerDown(event, appointment) {
    if (!isDraggable(appointment) || event.button !== 0) {
        return;
    }

    event.currentTarget.setPointerCapture(event.pointerId);

    const top = (effective(appointment).startMinutes - dayStart.value) * pxPerMinute;

    dragging.value = {
        id: appointment.id,
        appointment,
        pointerId: event.pointerId,
        startClientY: event.clientY,
        originTop: top,
        top,
        columnKey: appointment.columnKey,
        targetKey: appointment.columnKey,
        moved: false,
    };
}

function onBlockPointerMove(event) {
    if (!dragging.value || dragging.value.pointerId !== event.pointerId) {
        return;
    }

    const dy = event.clientY - dragging.value.startClientY;

    if (Math.abs(dy) > DRAG_THRESHOLD) {
        dragging.value.moved = true;
    }

    let newTop = dragging.value.originTop + dy;
    newTop = Math.round(newTop / slotPx.value) * slotPx.value;
    newTop = Math.min(Math.max(newTop, 0), totalHeight.value - slotPx.value);
    dragging.value.top = newTop;

    const columnKey = columnKeyAtPoint(event.clientX, event.clientY);
    dragging.value.targetKey = columnKey ?? dragging.value.columnKey;
}

function onBlockPointerUp(event, appointment) {
    if (!dragging.value || dragging.value.pointerId !== event.pointerId) {
        return;
    }

    const state = dragging.value;
    dragging.value = null;

    if (!state.moved) {
        emit('appointment-click', appointment);

        return;
    }

    const startMinutes = minutesFromTop(state.top);
    const targetKey = state.targetKey ?? state.columnKey;

    if (startMinutes === appointment.start_minutes && targetKey === appointment.columnKey) {
        return;
    }

    emit('reschedule', { id: appointment.id, columnKey: targetKey, startMinutes });
}

function onBlockPointerCancel() {
    dragging.value = null;
}
</script>

<template>
    <div ref="scroller" class="max-h-[calc(100vh-13rem)] overflow-auto rounded-xl border border-border">
        <div class="grid min-w-max" :style="gridStyle">
            <div class="sticky left-0 top-0 z-30 border-b border-r border-border bg-surface" />
            <div
                v-for="column in columns"
                :key="`head-${column.key}`"
                class="sticky top-0 z-20 border-b border-l border-border bg-surface px-3 py-2 text-center"
            >
                <div class="flex items-center justify-center gap-2">
                    <span v-if="column.color" class="size-2.5 rounded-full" :style="{ backgroundColor: column.color }" />
                    <span class="truncate text-sm font-semibold">{{ column.label }}</span>
                </div>
                <p v-if="column.sublabel" class="mt-0.5 truncate text-xs text-secondary">{{ column.sublabel }}</p>
            </div>

            <div class="sticky left-0 z-10 border-r border-border bg-surface" :style="{ height: `${totalHeight}px` }">
                <div
                    v-for="hour in hours"
                    :key="hour.label"
                    class="absolute -translate-y-1/2 pr-2 text-right text-xs text-muted"
                    :style="{ top: `${hour.top}px`, right: '0' }"
                >
                    {{ hour.label }}
                </div>
            </div>

            <div
                v-for="column in columns"
                :key="`col-${column.key}`"
                :data-col-key="column.key"
                class="relative border-l border-border transition-colors"
                :class="{ 'bg-primary/5': isDropTarget(column.key) }"
                :style="{ height: `${totalHeight}px` }"
            >
                <div class="absolute inset-0" @click="onColumnClick(column.key, $event)">
                    <div
                        v-for="index in lineCount"
                        :key="index"
                        :class="(index * lineMinutes) % 60 === 0 ? 'schedule-slot-hour' : 'schedule-slot'"
                        :style="{ height: `${linePx}px` }"
                    />
                </div>

                <div
                    v-if="showNowLine(column.key)"
                    class="pointer-events-none absolute left-0 right-0 z-20 border-t-2 border-danger"
                    :style="{ top: `${nowLineTop()}px` }"
                >
                    <span class="absolute -top-1 left-0 size-2 -translate-x-1/2 rounded-full bg-danger" />
                </div>

                <AppointmentBlock
                    v-for="appointment in blocksFor(column.key)"
                    :key="appointment.id"
                    :appointment="appointment"
                    :draggable="isDraggable(appointment)"
                    :dragging="dragging?.id === appointment.id"
                    :compact="appointment.duration_minutes < 30"
                    :style="blockStyle(appointment)"
                    @pointerdown="onBlockPointerDown($event, appointment)"
                    @pointermove="onBlockPointerMove($event)"
                    @pointerup="onBlockPointerUp($event, appointment)"
                    @pointercancel="onBlockPointerCancel"
                    @click="!isDraggable(appointment) && emit('appointment-click', appointment)"
                />
            </div>
        </div>
    </div>
</template>
