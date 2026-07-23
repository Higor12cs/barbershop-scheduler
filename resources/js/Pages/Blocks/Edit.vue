<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import BlockForm from './BlockForm.vue';

const props = defineProps({
    block: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
});

const form = useForm({
    employee_id: props.block.employee_id ?? '',
    all_day: props.block.all_day,
    start_date: props.block.start_date,
    end_date: props.block.end_date,
    start_time: props.block.start_time,
    end_time: props.block.end_time,
    reason: props.block.reason ?? '',
});

function submit() {
    form
        .transform((data) => ({ ...data, employee_id: data.employee_id || null }))
        .put(route('blocks.update', props.block.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Bloqueio" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Bloqueio</h1>
                    <p class="page-header-subtitle">Atualize o período bloqueado na agenda.</p>
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
