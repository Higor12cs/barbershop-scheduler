<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import Icon from "../../Components/Icon.vue";
import EmptyState from "../../Components/EmptyState.vue";
import ConfirmDialog from "../../Components/ConfirmDialog.vue";
import Pagination from "../../Components/Pagination.vue";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? "");
const confirming = ref(null);
const deleting = ref(false);

let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route("customers.index"), value ? { search: value } : {}, {
            preserveState: true,
            replace: true,
        });
    }, 350);
});

function confirmDelete(customer) {
    confirming.value = customer;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("customers.destroy", confirming.value.id), {
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
        <Head title="Clientes" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Clientes</h1>
                    <p class="page-header-subtitle">
                        Gerencie os clientes da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('customers.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Cliente
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-border p-4">
                    <div class="relative max-w-sm">
                        <Icon
                            name="search"
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"
                        />
                        <input
                            v-model="search"
                            type="search"
                            class="form-control pl-9"
                            placeholder="Buscar por nome ou telefone..."
                        />
                    </div>
                </div>

                <div v-if="customers.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Nome</th>
                                <th class="px-5 py-3 font-medium">Telefone</th>
                                <th class="px-5 py-3 font-medium">E-mail</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3 font-medium">
                                    <Link
                                        :href="route('customers.show', customer.id)"
                                        class="transition-colors hover:text-primary"
                                    >
                                        {{ customer.name }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ customer.phone }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ customer.email ?? "—" }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="badge"
                                        :class="
                                            customer.active
                                                ? 'badge-success'
                                                : 'badge-danger'
                                        "
                                    >
                                        {{
                                            customer.active
                                                ? "Ativo"
                                                : "Inativo"
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'customers.edit',
                                                    customer.id,
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
                                            @click="confirmDelete(customer)"
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
                    icon="users"
                    title="Nenhum Cliente Encontrado"
                    :description="
                        search
                            ? 'Nenhum cliente corresponde à busca realizada.'
                            : 'Cadastre o primeiro cliente para começar.'
                    "
                >
                    <Link
                        v-if="!search"
                        :href="route('customers.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Cliente
                    </Link>
                </EmptyState>

                <div
                    v-if="customers.data.length"
                    class="flex justify-end border-t border-border p-4"
                >
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Cliente"
            :message="`Tem certeza que deseja remover o cliente ${confirming?.name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
