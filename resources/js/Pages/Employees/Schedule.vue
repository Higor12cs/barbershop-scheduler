<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    days: { type: Array, default: () => [] },
    defaults: { type: Object, required: true },
});

const form = useForm({
    days: props.days.map((day) => ({
        weekday: day.weekday,
        label: day.label,
        ranges: day.ranges.map((range) => ({ ...range })),
    })),
});

const totalRanges = computed(() => form.days.reduce((total, day) => total + day.ranges.length, 0));

function rangeError(dayIndex, rangeIndex) {
    return form.errors[`days.${dayIndex}.ranges.${rangeIndex}.end`] ?? form.errors[`days.${dayIndex}.ranges.${rangeIndex}.start`];
}

function addRange(day) {
    const last = day.ranges[day.ranges.length - 1];

    day.ranges.push(
        last ? { start: last.end, end: props.defaults.end } : { start: props.defaults.start, end: props.defaults.end },
    );
}

function removeRange(day, index) {
    day.ranges.splice(index, 1);
}

function clearDay(day) {
    day.ranges = [];
}

function copyToWeekdays(day) {
    form.days.forEach((target) => {
        if (target.weekday >= 1 && target.weekday <= 5) {
            target.ranges = day.ranges.map((range) => ({ ...range }));
        }
    });
}

function submit() {
    form
        .transform((data) => ({
            days: data.days.map((day) => ({ weekday: day.weekday, ranges: day.ranges })),
        }))
        .put(route('employees.schedule.update', props.employee.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Jornada de ${employee.name}`" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Jornada de Trabalho</h1>
                    <p class="page-header-subtitle">
                        Defina os horários em que
                        <span class="font-medium text-foreground">{{ employee.name }}</span>
                        atende. Os intervalos entre as faixas ficam indisponíveis na agenda.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('employees.index')" class="btn btn-secondary">Voltar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Jornada' }}
                    </button>
                </div>
            </div>

            <div
                v-if="totalRanges === 0"
                class="flex items-start gap-3 rounded-xl border border-border bg-surface-muted p-4 text-sm text-secondary"
            >
                <Icon name="info" class="mt-0.5 size-4 shrink-0" />
                <p>
                    Sem nenhuma faixa cadastrada, este funcionário fica disponível o dia todo na agenda. Adicione faixas
                    para restringir os horários.
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Horários por Dia da Semana</h2>
                </div>
                <div class="card-body divide-y divide-border">
                    <div
                        v-for="(day, dayIndex) in form.days"
                        :key="day.weekday"
                        class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:gap-6"
                    >
                        <div class="flex items-center justify-between gap-3 sm:w-44 sm:shrink-0">
                            <span class="text-sm font-medium">{{ day.label }}</span>
                            <span v-if="day.ranges.length === 0" class="badge badge-muted">Folga</span>
                        </div>

                        <div class="flex-1 space-y-2">
                            <div
                                v-for="(range, rangeIndex) in day.ranges"
                                :key="rangeIndex"
                                class="space-y-1"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <input v-model="range.start" type="time" step="300" class="form-control max-w-32">
                                    <span class="text-sm text-muted">até</span>
                                    <input v-model="range.end" type="time" step="300" class="form-control max-w-32">
                                    <button
                                        type="button"
                                        class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                        title="Remover faixa"
                                        @click="removeRange(day, rangeIndex)"
                                    >
                                        <Icon name="trash" class="size-4" />
                                    </button>
                                </div>
                                <p v-if="rangeError(dayIndex, rangeIndex)" class="text-xs text-danger">
                                    {{ rangeError(dayIndex, rangeIndex) }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" class="btn btn-secondary px-3 py-1.5 text-xs" @click="addRange(day)">
                                    <Icon name="plus" class="size-4" />
                                    Adicionar faixa
                                </button>
                                <button
                                    v-if="day.ranges.length"
                                    type="button"
                                    class="text-xs text-secondary underline-offset-2 hover:underline"
                                    @click="clearDay(day)"
                                >
                                    Marcar como folga
                                </button>
                                <button
                                    v-if="day.ranges.length"
                                    type="button"
                                    class="text-xs text-secondary underline-offset-2 hover:underline"
                                    @click="copyToWeekdays(day)"
                                >
                                    Copiar para seg–sex
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
