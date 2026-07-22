<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import TenantForm from './TenantForm.vue';

const props = defineProps({
    moduleOptions: { type: Array, default: () => [] },
    defaultModules: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    slug: '',
    active: true,
    access_until: '',
    modules: [...props.defaultModules],
    whatsapp_provider: '',
    whatsapp_config: { base_url: '', token: '' },
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

function submit() {
    form.post(route('admin.tenants.store'));
}
</script>

<template>
    <AdminLayout>
        <Head title="Novo Ambiente" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Novo Ambiente</h1>
                    <p class="page-header-subtitle">Cadastre uma nova barbearia e o seu administrador</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('admin.tenants.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Criar Ambiente' }}
                    </button>
                </div>
            </div>

            <TenantForm :form="form" :module-options="moduleOptions" :provider-options="providerOptions" show-admin-fields />
        </form>
    </AdminLayout>
</template>
