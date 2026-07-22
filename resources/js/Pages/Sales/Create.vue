<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import SaleForm from './SaleForm.vue';
import { todayISO } from '../../Support/date.js';

defineProps({
    customers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

function currentTime() {
    const now = new Date();

    return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
}

const form = useForm({
    customer_id: null,
    employee_id: '',
    date: todayISO(),
    time: currentTime(),
    items: [{ product_id: '', quantity: 1, unit_price: '' }],
});

function submit() {
    form.transform((data) => ({
        ...data,
        employee_id: data.employee_id ? Number(data.employee_id) : null,
    })).post(route('sales.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Nova Venda" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Registrar Nova Venda</h1>
                    <p class="page-header-subtitle">Venda de balcão com produtos e serviços</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('sales.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Venda' }}
                    </button>
                </div>
            </div>

            <SaleForm :form="form" :customers="customers" :employees="employees" :products="products" />
        </form>
    </AppLayout>
</template>
