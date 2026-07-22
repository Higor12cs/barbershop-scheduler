<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';
import TenantForm from './TenantForm.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    moduleOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    active: props.tenant.active,
    access_until: props.tenant.access_until ?? '',
    modules: [...(props.tenant.modules ?? [])],
    whatsapp_provider: props.tenant.whatsapp_provider ?? '',
    whatsapp_config: {
        base_url: props.tenant.whatsapp_config?.base_url ?? '',
        token: props.tenant.whatsapp_config?.token ?? '',
    },
});

function submit() {
    form.put(route('admin.tenants.update', props.tenant.id));
}
</script>

<template>
    <AdminLayout>
        <Head title="Editar Ambiente" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Ambiente</h1>
                    <p class="page-header-subtitle">{{ tenant.name }}</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('admin.tenants.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Alterações' }}
                    </button>
                </div>
            </div>

            <TenantForm
                :form="form"
                :module-options="moduleOptions"
                :provider-options="providerOptions"
                :webhook-url="tenant.webhook_url"
            />
        </form>
    </AdminLayout>
</template>
