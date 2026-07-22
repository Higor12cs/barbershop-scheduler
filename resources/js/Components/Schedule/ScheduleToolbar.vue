<script setup>
import Icon from '../Icon.vue';
import { formatLongDate } from '../../Support/date.js';

const props = defineProps({
    date: { type: String, required: true },
    view: { type: String, required: true },
    selectedEmployeeId: { type: [Number, null], default: null },
    employees: { type: Array, default: () => [] },
});

const emit = defineEmits(['prev', 'next', 'today', 'change-view', 'change-date', 'select-employee', 'create']);
</script>

<template>
    <div class="space-y-4">
        <div class="page-header">
            <div>
                <h1 class="page-header-title">Agenda</h1>
                <p class="page-header-subtitle">{{ formatLongDate(date) }}</p>
            </div>
            <div class="page-header-actions">
                <button type="button" class="btn btn-primary" @click="emit('create')">
                    <Icon name="plus" class="size-4" />
                    Novo Agendamento
                </button>
            </div>
        </div>

        <div class="card flex flex-col gap-3 p-3 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between">
            <div class="flex items-center gap-2">
                <button type="button" class="btn btn-secondary px-2" @click="emit('prev')">
                    <Icon name="chevron-left" class="size-5" />
                </button>
                <button type="button" class="btn btn-secondary" @click="emit('today')">Hoje</button>
                <button type="button" class="btn btn-secondary px-2" @click="emit('next')">
                    <Icon name="chevron-right" class="size-5" />
                </button>
                <input
                    :value="date"
                    type="date"
                    class="form-control w-auto"
                    @change="emit('change-date', $event.target.value)"
                >
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 rounded-lg border border-border p-1">
                    <button
                        type="button"
                        class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                        :class="view === 'day' ? 'bg-primary text-primary-foreground' : 'text-secondary hover:bg-surface-muted'"
                        @click="emit('change-view', 'day')"
                    >
                        Dia
                    </button>
                    <button
                        type="button"
                        class="rounded-md px-3 py-1 text-sm font-medium transition-colors"
                        :class="view === 'week' ? 'bg-primary text-primary-foreground' : 'text-secondary hover:bg-surface-muted'"
                        @click="emit('change-view', 'week')"
                    >
                        Semana
                    </button>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                v-if="view === 'day'"
                type="button"
                class="pill px-3 py-1.5 text-sm font-medium"
                :class="{ 'pill-active': selectedEmployeeId === null }"
                @click="emit('select-employee', null)"
            >
                Todos
            </button>
            <button
                v-for="employee in employees"
                :key="employee.id"
                type="button"
                class="pill px-3 py-1.5 text-sm font-medium"
                :class="{ 'pill-active': selectedEmployeeId === employee.id }"
                @click="emit('select-employee', employee.id)"
            >
                <span class="size-2.5 rounded-full" :style="{ backgroundColor: employee.color }" />
                {{ employee.name }}
            </button>
        </div>
    </div>
</template>
