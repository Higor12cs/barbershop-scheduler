<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import RoleForm from './RoleForm.vue';

const props = defineProps({
    role: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.role.name,
    permissions: [...(props.role.permissions ?? [])],
});

function submit() {
    form.put(route('settings.roles.update', props.role.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Papel" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Papel</h1>
                    <p class="page-header-subtitle">{{ role.name }}</p>
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
