<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import RecurrenceForm from './RecurrenceForm.vue';
import { todayISO } from '../../Support/date.js';

defineProps({
    customers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
});

const form = useForm({
    customer_id: null,
    product_id: '',
    employee_id: '',
    time: '',
    interval_days: 7,
    starts_on: todayISO(),
    ends_on: null,
    notes: '',
    active: true,
});

function submit() {
    form.post(route('recurrences.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Nova Recorrência" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Nova Recorrência</h1>
                    <p class="page-header-subtitle">Cadastre um atendimento recorrente</p>
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
