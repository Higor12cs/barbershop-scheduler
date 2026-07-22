<script setup>
const props = defineProps({
    period: { type: String, default: 'month' },
    dateFrom: { type: String, default: '' },
    dateTo: { type: String, default: '' },
});

const emit = defineEmits(['change']);

const presets = [
    { value: 'today', label: 'Hoje' },
    { value: 'week', label: 'Esta Semana' },
    { value: 'month', label: 'Este Mês' },
    { value: 'custom', label: 'Personalizado' },
];

function setPeriod(value) {
    emit('change', { period: value, date_from: props.dateFrom, date_to: props.dateTo });
}

function setDateFrom(value) {
    emit('change', { period: 'custom', date_from: value, date_to: props.dateTo });
}

function setDateTo(value) {
    emit('change', { period: 'custom', date_from: props.dateFrom, date_to: value });
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="preset in presets"
            :key="preset.value"
            type="button"
            class="pill px-3 py-1.5 text-sm font-medium"
            :class="{ 'pill-active': period === preset.value }"
            @click="setPeriod(preset.value)"
        >
            {{ preset.label }}
        </button>

        <template v-if="period === 'custom'">
            <input
                :value="dateFrom"
                type="date"
                class="form-control h-9 w-auto"
                @change="setDateFrom($event.target.value)"
            >
            <span class="text-sm text-secondary">até</span>
            <input
                :value="dateTo"
                type="date"
                class="form-control h-9 w-auto"
                @change="setDateTo($event.target.value)"
            >
        </template>
    </div>
</template>
