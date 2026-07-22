<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Icon from '../../../Components/Icon.vue';
import EmptyState from '../../../Components/EmptyState.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineProps({
    tenants: { type: Array, default: () => [] },
});

const confirming = ref(null);
const deleting = ref(false);

function confirmDelete(tenant) {
    confirming.value = tenant;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route('admin.tenants.destroy', confirming.value.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = null;
        },
    });
}

function statusLabel(tenant) {
    if (!tenant.active) {
        return { text: 'Desativado', class: 'badge-danger' };
    }

    if (tenant.expired) {
        return { text: 'Expirado', class: 'badge-warning' };
    }

    return { text: 'Ativo', class: 'badge-success' };
}

function formatDate(value) {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString('pt-BR');
}
</script>

<template>
    <AdminLayout>
        <Head title="Ambientes" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Ambientes</h1>
                    <p class="page-header-subtitle">Gerencie as barbearias da plataforma</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('admin.tenants.create')" class="btn btn-primary">
                        <Icon name="plus" class="size-4" />
                        Novo Ambiente
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div v-if="tenants.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted">
                                <th class="px-5 py-3 font-medium">Nome</th>
                                <th class="px-5 py-3 font-medium">Slug</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Acesso até</th>
                                <th class="px-5 py-3 font-medium">Usuários</th>
                                <th class="px-5 py-3 text-right font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tenant in tenants" :key="tenant.id" class="border-b border-border last:border-0">
                                <td class="px-5 py-3 font-medium">{{ tenant.name }}</td>
                                <td class="px-5 py-3">
                                    <span class="font-mono text-xs text-secondary">{{ tenant.slug }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge" :class="statusLabel(tenant).class">{{ statusLabel(tenant).text }}</span>
                                </td>
                                <td class="px-5 py-3 text-secondary">{{ formatDate(tenant.access_until) }}</td>
                                <td class="px-5 py-3 text-secondary">{{ tenant.users_count }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="route('admin.tenants.edit', tenant.id)"
                                            class="rounded-lg border border-border p-2 text-secondary transition-colors hover:bg-surface-muted"
                                        >
                                            <Icon name="pencil" class="size-4" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                            @click="confirmDelete(tenant)"
                                        >
                                            <Icon name="trash" class="size-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState
                    v-else
                    icon="building"
                    title="Nenhum Ambiente Cadastrado"
                    description="Crie o primeiro ambiente para começar a usar a plataforma."
                >
                    <Link :href="route('admin.tenants.create')" class="btn btn-primary">
                        <Icon name="plus" class="size-4" />
                        Novo Ambiente
                    </Link>
                </EmptyState>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Ambiente"
            :message="`Tem certeza que deseja remover o ambiente ${confirming?.name}? Todos os dados e o banco de dados serão apagados permanentemente.`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AdminLayout>
</template>
