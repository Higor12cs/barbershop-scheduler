<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import UserForm from './UserForm.vue';

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role ?? '',
});

function submit() {
    form.put(route('settings.users.update', props.user.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Usuário" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Usuário</h1>
                    <p class="page-header-subtitle">{{ user.name }}</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('settings.users.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Usuário' }}
                    </button>
                </div>
            </div>

            <UserForm :form="form" :roles="roles" editing :name-locked="user.belongs_to_other_tenant" />
        </form>
    </AppLayout>
</template>
