<script setup>
import { Head } from '@inertiajs/vue3';
import Icon from '../../Components/Icon.vue';
import AdminLayout from '../../Layouts/AdminLayout.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
});

const cards = [
    { key: 'tenants', label: 'Total de Ambientes', icon: 'building' },
    { key: 'active', label: 'Ativos', icon: 'check' },
    { key: 'expired', label: 'Expirados', icon: 'alert-triangle' },
    { key: 'users', label: 'Usuários', icon: 'users' },
];
</script>

<template>
    <AdminLayout>
        <Head title="Painel Administrativo" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Painel Administrativo</h1>
                    <p class="page-header-subtitle">Visão geral dos ambientes da plataforma</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="card in cards" :key="card.key" class="card">
                    <div class="card-body flex items-center justify-between">
                        <div>
                            <p class="text-sm text-secondary">{{ card.label }}</p>
                            <p class="mt-1 text-2xl font-semibold">{{ props.stats[card.key] ?? 0 }}</p>
                        </div>
                        <span class="flex size-10 items-center justify-center rounded-xl bg-surface-muted text-secondary">
                            <Icon :name="card.icon" class="size-5" />
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
