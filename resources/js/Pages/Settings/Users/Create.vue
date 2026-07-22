<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import UserForm from './UserForm.vue';

defineProps({
    roles: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
});

function submit() {
    form.post(route('settings.users.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Usuário" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Criar Novo Usuário</h1>
                    <p class="page-header-subtitle">Cadastre ou vincule um usuário a este ambiente</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('settings.users.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Usuário' }}
                    </button>
                </div>
            </div>

            <UserForm :form="form" :roles="roles" />
        </form>
    </AppLayout>
</template>
