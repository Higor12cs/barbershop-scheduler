<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import Icon from "../../Components/Icon.vue";
import EmptyState from "../../Components/EmptyState.vue";
import ConfirmDialog from "../../Components/ConfirmDialog.vue";
import Pagination from "../../Components/Pagination.vue";
import PeriodFilter from "../../Components/PeriodFilter.vue";
import SelectInput from "../../Components/SelectInput.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import { formatBRL } from "../../Support/money.js";

const props = defineProps({
    sales: { type: Object, required: true },
    summary: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? "");
const employeeId = ref(props.filters.employee_id ?? "");
const confirming = ref(null);
const deleting = ref(false);

let searchTimeout = null;

function reload(overrides = {}) {
    const params = {
        period: props.filters.period,
        ...(props.filters.period === "custom"
            ? {
                  date_from: props.filters.date_from,
                  date_to: props.filters.date_to,
              }
            : {}),
        ...(employeeId.value ? { employee_id: employeeId.value } : {}),
        ...(search.value ? { search: search.value } : {}),
        ...overrides,
    };

    router.get(route("sales.index"), params, {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => reload(), 350);
});

function onPeriodChange({ period, date_from, date_to }) {
    reload(
        period === "custom"
            ? { period, date_from, date_to }
            : { period, date_from: undefined, date_to: undefined },
    );
}

function onEmployeeChange(value) {
    employeeId.value = value;
    reload(value ? { employee_id: value } : { employee_id: undefined });
}

function confirmDelete(sale) {
    confirming.value = sale;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("sales.destroy", confirming.value.id), {
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
        <Head title="Vendas" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Registro de Vendas</h1>
                    <p class="page-header-subtitle">
                        Acompanhe as vendas da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('sales.create')" class="btn btn-primary">
                        <Icon name="plus" class="size-4" />
                        Nova Venda
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="card p-5">
                    <p class="text-sm text-secondary">Total do Período</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ formatBRL(summary.total) }}
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Quantidade de Vendas</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ summary.count }}
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Ticket Médio</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ formatBRL(summary.average) }}
                    </p>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-border p-4">
                    <PeriodFilter
                        :period="filters.period"
                        :date-from="filters.date_from"
                        :date-to="filters.date_to"
                        @change="onPeriodChange"
                    />
                    <div
                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="w-full sm:max-w-56">
                            <SelectInput
                                :model-value="employeeId"
                                :options="employees"
                                placeholder="Todos os Funcionários"
                                @update:model-value="onEmployeeChange"
                            />
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
                                placeholder="Buscar por cliente..."
                            />
                        </div>
                    </div>
                </div>

                <div v-if="sales.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Data</th>
                                <th class="px-5 py-3 font-medium">Cliente</th>
                                <th class="px-5 py-3 font-medium">
                                    Funcionário
                                </th>
                                <th class="px-5 py-3 font-medium">Itens</th>
                                <th class="px-5 py-3 font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Origem</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="sale in sales.data"
                                :key="sale.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3 text-secondary">
                                    {{ sale.sold_at }}
                                </td>
                                <td class="px-5 py-3 font-medium">
                                    {{ sale.customer_name }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ sale.employee_name ?? "—" }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ sale.items_summary }}
                                </td>
                                <td class="px-5 py-3 font-medium">
                                    {{ formatBRL(sale.total) }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="badge"
                                        :class="
                                            sale.from_appointment
                                                ? 'badge-info'
                                                : ''
                                        "
                                    >
                                        {{
                                            sale.from_appointment
                                                ? "Agendamento"
                                                : "Manual"
                                        }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Link
                                            :href="route('sales.show', sale.id)"
                                            class="rounded-lg border border-border p-2 text-secondary transition-colors hover:bg-surface-muted"
                                        >
                                            <Icon name="eye" class="size-4" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                            @click="confirmDelete(sale)"
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
                    icon="shopping-cart"
                    title="Nenhuma Venda Encontrada"
                    :description="
                        search || employeeId
                            ? 'Nenhuma venda corresponde aos filtros aplicados.'
                            : 'Nenhuma venda registrada no período selecionado.'
                    "
                >
                    <Link
                        v-if="!search && !employeeId"
                        :href="route('sales.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Nova Venda
                    </Link>
                </EmptyState>

                <div
                    v-if="sales.data.length"
                    class="flex justify-end border-t border-border p-4"
                >
                    <Pagination :links="sales.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Venda"
            :message="`Tem certeza que deseja remover a venda de ${confirming?.customer_name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
