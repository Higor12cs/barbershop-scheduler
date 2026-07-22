<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ProductForm from './ProductForm.vue';

const props = defineProps({
    product: { type: Object, required: true },
    types: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.product.name,
    type: props.product.type,
    price: props.product.price,
    cost: props.product.cost ?? '',
    duration_minutes: props.product.duration_minutes ?? '',
    description: props.product.description ?? '',
    active: props.product.active,
});

function submit() {
    form.put(route('products.update', props.product.id));
}
</script>

<template>
    <AppLayout>
        <Head title="Editar Produto" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Editar Produto</h1>
                    <p class="page-header-subtitle">{{ product.name }}</p>
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
