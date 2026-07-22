<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import Icon from "../../Components/Icon.vue";
import EmptyState from "../../Components/EmptyState.vue";
import ConfirmDialog from "../../Components/ConfirmDialog.vue";
import Pagination from "../../Components/Pagination.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import { formatBRL } from "../../Support/money.js";

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    types: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? "");
const type = ref(props.filters.type ?? "");
const confirming = ref(null);
const deleting = ref(false);

const typeFilters = [
    { value: "", label: "Todos" },
    { value: "product", label: "Produtos" },
    { value: "service", label: "Serviços" },
];

let searchTimeout = null;

function reload() {
    const params = {};

    if (search.value) {
        params.search = search.value;
    }

    if (type.value) {
        params.type = type.value;
    }

    router.get(route("products.index"), params, {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 350);
});

function setType(value) {
    type.value = value;
    reload();
}

function confirmDelete(product) {
    confirming.value = product;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("products.destroy", confirming.value.id), {
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
        <Head title="Produtos" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Produtos</h1>
                    <p class="page-header-subtitle">
                        Gerencie os produtos e serviços da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('products.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Produto
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div
                    class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-2">
                        <button
                            v-for="filter in typeFilters"
                            :key="filter.value"
                            type="button"
                            class="pill px-3 py-1.5 text-sm font-medium"
                            :class="{ 'pill-active': type === filter.value }"
                            @click="setType(filter.value)"
                        >
                            {{ filter.label }}
                        </button>
                    </div>
                    <div class="relative w-full sm:max-w-xs">
                        <Icon
                            name="search"
                            class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted"
                        />
                        <input
                            v-model="search"
                            type="search"
                            class="form-control pl-9"
                            placeholder="Buscar por nome..."
                        />
                    </div>
                </div>

                <div v-if="products.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Nome</th>
                                <th class="px-5 py-3 font-medium">Tipo</th>
                                <th class="px-5 py-3 font-medium">Preço</th>
                                <th class="px-5 py-3 font-medium">Duração</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3 font-medium">
                                    {{ product.name }}
                                </td>
                                <td class="px-5 py-3">
                                    <span class="badge">{{
                                        product.type_label
                                    }}</span>
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ formatBRL(product.price) }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{
                                        product.type === "service" &&
                                        product.duration_minutes
                                            ? `${product.duration_minutes} min`
                                            : "—"
                                    }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="badge"
                                        :class="
                                            product.active
                                                ? 'badge-success'
                                                : 'badge-danger'
                                        "
                                    >
                                        {{
                                            product.active ? "Ativo" : "Inativo"
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
                                                    'products.edit',
                                                    product.id,
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
                                            @click="confirmDelete(product)"
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
                    icon="package"
                    title="Nenhum Produto Encontrado"
                    :description="
                        search || type
                            ? 'Nenhum item corresponde aos filtros aplicados.'
                            : 'Cadastre o primeiro produto ou serviço para começar.'
                    "
                >
                    <Link
                        v-if="!search && !type"
                        :href="route('products.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Produto
                    </Link>
                </EmptyState>

                <div
                    v-if="products.data.length"
                    class="flex justify-end border-t border-border p-4"
                >
                    <Pagination :links="products.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Produto"
            :message="`Tem certeza que deseja remover ${confirming?.name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
