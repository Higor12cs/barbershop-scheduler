<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import BlockForm from './BlockForm.vue';
import { todayISO } from '../../Support/date.js';

defineProps({
    employees: { type: Array, default: () => [] },
});

const form = useForm({
    employee_id: '',
    all_day: true,
    start_date: todayISO(),
    end_date: todayISO(),
    start_time: '08:00',
    end_time: '18:00',
    reason: '',
});

function submit() {
    form.transform((data) => ({ ...data, employee_id: data.employee_id || null })).post(route('blocks.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Bloqueio" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Novo Bloqueio</h1>
                    <p class="page-header-subtitle">Impeça agendamentos em um período específico.</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('blocks.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Bloqueio' }}
                    </button>
                </div>
            </div>

            <BlockForm :form="form" :employees="employees" />
        </form>
    </AppLayout>
</template>
