<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import Icon from '../Components/Icon.vue';
import GuestLayout from '../Layouts/GuestLayout.vue';

const props = defineProps({
    reason: { type: String, default: 'disabled' },
    tenantName: { type: String, default: null },
});

const content = computed(() =>
    props.reason === 'expired'
        ? {
              title: 'Acesso Expirado',
              description: 'O período de acesso deste ambiente expirou. Entre em contato com o suporte para renovar.',
          }
        : {
              title: 'Conta Desativada',
              description: 'Este ambiente está desativado no momento. Entre em contato com o suporte para mais informações.',
          },
);

function changeTenant() {
    router.get(route('tenant-selection.index'));
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <GuestLayout>
        <Head :title="content.title" />

        <div class="flex flex-col items-center gap-4 text-center">
            <span class="flex size-14 items-center justify-center rounded-2xl bg-danger/10 text-danger">
                <Icon name="alert-triangle" class="size-7" />
            </span>

            <div class="space-y-1">
                <h1 class="text-xl font-semibold">{{ content.title }}</h1>
                <p v-if="tenantName" class="text-sm font-medium text-secondary">{{ tenantName }}</p>
                <p class="text-sm text-secondary">{{ content.description }}</p>
            </div>

            <div class="flex w-full flex-col gap-2">
                <button type="button" class="btn btn-primary btn-block" @click="changeTenant">
                    <Icon name="building" class="size-4" />
                    Trocar de Ambiente
                </button>
                <button type="button" class="btn btn-secondary btn-block" @click="logout">
                    <Icon name="log-out" class="size-4" />
                    Sair
                </button>
            </div>
        </div>
    </GuestLayout>
</template>
