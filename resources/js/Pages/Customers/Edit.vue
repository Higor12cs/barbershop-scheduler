<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import CustomerForm from './CustomerForm.vue';

const props = defineProps({
    customer: { type: Object, required: true },
});

const form = useForm({
    name: props.customer.name,
    phone: props.customer.phone,
    email: props.customer.email ?? '',
    birth_date: props.customer.birth_date ?? '',
    notes: props.customer.notes ?? '',
    active: props.customer.active,
});

function submit() {
    form.put(route('customers.update', props.customer.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Cliente" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Cliente</h1>
                    <p class="page-header-subtitle">{{ customer.name }}</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('customers.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Cliente' }}
                    </button>
                </div>
            </div>

            <CustomerForm :form="form" />
        </form>
    </AppLayout>
</template>
