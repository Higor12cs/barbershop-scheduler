<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductForm from './ProductForm.vue';

defineProps({
    types: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    type: 'service',
    price: '',
    cost: '',
    duration_minutes: '',
    description: '',
    active: true,
});

function submit() {
    form.post(route('products.store'));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Produto" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Criar Novo Produto</h1>
                    <p class="page-header-subtitle">Cadastre um novo produto ou serviço</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('products.index')" class="btn btn-secondary">Cancelar</Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar Produto' }}
                    </button>
                </div>
            </div>

            <ProductForm :form="form" :types="types" />
        </form>
    </AppLayout>
</template>
