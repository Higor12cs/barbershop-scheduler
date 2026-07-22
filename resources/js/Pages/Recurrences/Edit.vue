<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import RecurrenceForm from './RecurrenceForm.vue';

const props = defineProps({
    recurrence: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
});

const form = useForm({
    customer_id: props.recurrence.customer_id,
    product_id: props.recurrence.product_id,
    employee_id: props.recurrence.employee_id,
    time: props.recurrence.time,
    interval_days: props.recurrence.interval_days,
    starts_on: props.recurrence.starts_on,
    ends_on: props.recurrence.ends_on,
    notes: props.recurrence.notes ?? '',
    active: props.recurrence.active,
});

function submit() {
    form.put(route('recurrences.update', props.recurrence.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Recorrência" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Recorrência</h1>
                    <p class="page-header-subtitle">Atualize os dados do atendimento recorrente</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('recurrences.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Recorrência' }}
                    </button>
                </div>
            </div>

            <RecurrenceForm
                :form="form"
                :customers="customers"
                :employees="employees"
                :services="services"
                :time-slots="timeSlots"
            />
        </form>
    </AppLayout>
</template>
