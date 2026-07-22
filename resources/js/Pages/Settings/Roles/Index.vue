<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import EmptyState from "../../../Components/EmptyState.vue";
import ConfirmDialog from "../../../Components/ConfirmDialog.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

defineProps({
    roles: { type: Array, default: () => [] },
});

const confirming = ref(null);
const deleting = ref(false);

function confirmDelete(role) {
    confirming.value = role;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("settings.roles.destroy", confirming.value.id), {
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
        <Head title="Papéis" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Papéis</h1>
                    <p class="page-header-subtitle">
                        Defina os papéis e as permissões de acesso.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('settings.roles.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Papel
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div v-if="roles.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Nome</th>
                                <th class="px-5 py-3 font-medium">Usuários</th>
                                <th class="px-5 py-3 font-medium">
                                    Permissões
                                </th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="role in roles"
                                :key="role.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center gap-2 font-medium"
                                    >
                                        {{ role.name }}
                                        <span v-if="role.is_admin" class="badge"
                                            >Padrão</span
                                        >
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ role.users_count }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ role.permissions_count }}
                                </td>
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <template v-if="!role.is_admin">
                                            <Link
                                                :href="
                                                    route(
                                                        'settings.roles.edit',
                                                        role.id,
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
                                                type="button"
                                                class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                                @click="confirmDelete(role)"
                                            >
                                                <Icon
                                                    name="trash"
                                                    class="size-4"
                                                />
                                            </button>
                                        </template>
                                        <span v-else class="text-xs text-muted"
                                            >Não editável</span
                                        >
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <EmptyState
                    v-else
                    icon="key"
                    title="Nenhum Papel Cadastrado"
                    description="Crie o primeiro papel para organizar as permissões."
                >
                    <Link
                        :href="route('settings.roles.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Papel
                    </Link>
                </EmptyState>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Papel"
            :message="`Tem certeza que deseja remover o papel ${confirming?.name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
