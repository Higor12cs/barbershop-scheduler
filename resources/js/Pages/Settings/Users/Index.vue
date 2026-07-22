<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import EmptyState from "../../../Components/EmptyState.vue";
import ConfirmDialog from "../../../Components/ConfirmDialog.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

defineProps({
    users: { type: Array, default: () => [] },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const confirming = ref(null);
const deleting = ref(false);

function confirmDelete(user) {
    confirming.value = user;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("settings.users.destroy", confirming.value.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Usuários" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Usuários</h1>
                    <p class="page-header-subtitle">
                        Gerencie os usuários com acesso a este ambiente.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('settings.users.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Usuário
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div v-if="users.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Nome</th>
                                <th class="px-5 py-3 font-medium">E-mail</th>
                                <th class="px-5 py-3 font-medium">Papel</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3 font-medium">
                                    {{ user.name }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ user.email }}
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="user.role" class="badge">{{
                                        user.role
                                    }}</span>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'settings.users.edit',
                                                    user.id,
                                                )
                                            "
                                            class="rounded-lg border border-border p-2 text-secondary transition-colors hover:bg-surface-muted"
                                        >
                                            <Icon
                                                name="pencil"
                                                class="size-4"
                                            />
                                        </Link>
                                        <button
                                            v-if="user.id !== currentUserId"
                                            type="button"
                                            class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                            @click="confirmDelete(user)"
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
                    icon="shield"
                    title="Nenhum Usuário Cadastrado"
                    description="Cadastre o primeiro usuário para dar acesso a este ambiente."
                >
                    <Link
                        :href="route('settings.users.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Usuário
                    </Link>
                </EmptyState>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Usuário"
            :message="`Tem certeza que deseja remover ${confirming?.name} deste ambiente? O usuário não será excluído, apenas desvinculado.`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
