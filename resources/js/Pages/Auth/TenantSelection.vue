<script setup>
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import EmptyState from '../../Components/EmptyState.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const props = defineProps({
    tenants: { type: Array, default: () => [] },
    isSuperAdmin: { type: Boolean, default: false },
});

function select(tenant) {
    router.post(route('tenant-selection.store'), { tenant_id: tenant.id });
}

function goToAdmin() {
    router.get(route('admin.dashboard'));
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <GuestLayout title="Selecionar Ambiente" subtitle="Escolha em qual ambiente deseja trabalhar">
        <Head title="Selecionar Ambiente" />

        <div class="space-y-3">
            <button
                v-if="props.isSuperAdmin"
                type="button"
                class="pill w-full justify-between px-4 py-3"
                @click="goToAdmin"
            >
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Icon name="shield" class="size-5" />
                    </span>
                    <span class="text-left">
                        <span class="block text-sm font-semibold">Painel Administrativo</span>
                        <span class="block text-xs text-secondary">Gerenciar ambientes e usuários</span>
                    </span>
                </span>
                <Icon name="chevron-down" class="size-4 -rotate-90 text-muted" />
            </button>

            <button
                v-for="tenant in props.tenants"
                :key="tenant.id"
                type="button"
                class="pill w-full justify-between px-4 py-3"
                @click="select(tenant)"
            >
                <span class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-lg bg-surface-muted text-sm font-semibold text-secondary">
                        {{ tenant.name.charAt(0).toUpperCase() }}
                    </span>
                    <span class="text-left">
                        <span class="block text-sm font-semibold">{{ tenant.name }}</span>
                        <span class="block text-xs text-secondary">{{ tenant.slug }}</span>
                    </span>
                </span>
                <Icon name="chevron-down" class="size-4 -rotate-90 text-muted" />
            </button>

            <EmptyState
                v-if="!props.tenants.length && !props.isSuperAdmin"
                icon="building"
                title="Nenhum Ambiente Disponível"
                description="Você ainda não possui acesso a nenhum ambiente. Entre em contato com o suporte."
            />
        </div>

        <button type="button" class="btn btn-secondary btn-block mt-6" @click="logout">
            <Icon name="log-out" class="size-4" />
            Sair
        </button>
    </GuestLayout>
</template>
