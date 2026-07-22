<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import EmployeeForm from './EmployeeForm.vue';

const props = defineProps({
    employee: { type: Object, required: true },
    colors: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.employee.name,
    phone: props.employee.phone ?? '',
    email: props.employee.email ?? '',
    color: props.employee.color,
    active: props.employee.active,
});

function submit() {
    form.put(route('employees.update', props.employee.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Funcionário" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Funcionário</h1>
                    <p class="page-header-subtitle">{{ employee.name }}</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('employees.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Funcionário' }}
                    </button>
                </div>
            </div>

            <EmployeeForm :form="form" :colors="colors" />
        </form>
    </AppLayout>
</template>
