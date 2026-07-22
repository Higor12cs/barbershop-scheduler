<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import RoleForm from './RoleForm.vue';

defineProps({
    groups: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    permissions: [],
});

function submit() {
    form.post(route('settings.roles.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Papel" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Criar Novo Papel</h1>
                    <p class="page-header-subtitle">Defina o nome e as permissões do papel</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('settings.roles.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Papel' }}
                    </button>
                </div>
            </div>

            <RoleForm :form="form" :groups="groups" />
        </form>
    </AppLayout>
</template>
