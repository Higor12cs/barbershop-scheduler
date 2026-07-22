<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import EmployeeForm from './EmployeeForm.vue';

const props = defineProps({
    colors: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    phone: '',
    email: '',
    color: props.colors[0]?.value ?? '',
    active: true,
});

function submit() {
    form.post(route('employees.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Funcionário" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Criar Novo Funcionário</h1>
                    <p class="page-header-subtitle">Cadastre um novo membro da equipe</p>
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
