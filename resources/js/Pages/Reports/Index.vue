<script setup>
import { Head, router } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { route } from "ziggy-js";
import EmptyState from "../../Components/EmptyState.vue";
import Icon from "../../Components/Icon.vue";
import PeriodFilter from "../../Components/PeriodFilter.vue";
import SearchSelect from "../../Components/SearchSelect.vue";
import BarChart from "../../Components/Charts/BarChart.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import { formatBRL } from "../../Support/money.js";

const props = defineProps({
    tab: { type: String, required: true },
    filters: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    sales: { type: Object, default: null },
    appointments: { type: Object, default: null },
});

const employeeId = ref(props.filters.employee_id ?? "");
const customerId = ref(props.filters.customer_id ?? "");

const tabs = [
    { value: "sales", label: "Vendas" },
    { value: "appointments", label: "Agendamentos" },
];

function reload(overrides = {}) {
    const params = {
        tab: props.tab,
        period: props.filters.period,
        ...(props.filters.period === "custom"
            ? {
                  date_from: props.filters.date_from,
                  date_to: props.filters.date_to,
              }
            : {}),
        ...(employeeId.value ? { employee_id: employeeId.value } : {}),
        ...(customerId.value ? { customer_id: customerId.value } : {}),
        ...overrides,
    };

    router.get(route("reports.index"), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function setTab(value) {
    reload({ tab: value });
}

function onPeriodChange({ period, date_from, date_to }) {
    reload(
        period === "custom"
            ? { period, date_from, date_to }
            : { period, date_from: undefined, date_to: undefined },
    );
}

function onEmployeeChange(value) {
    employeeId.value = value ?? "";
    reload(value ? { employee_id: value } : { employee_id: undefined });
}

function onCustomerChange(value) {
    customerId.value = value ?? "";
    reload(value ? { customer_id: value } : { customer_id: undefined });
}

function formatRate(value) {
    return value === null ? "—" : `${value.toLocaleString("pt-BR")}%`;
}

const pdfHref = computed(() => {
    const params = {
        period: props.filters.period,
        ...(props.filters.period === "custom"
            ? {
                  date_from: props.filters.date_from,
                  date_to: props.filters.date_to,
              }
            : {}),
        ...(employeeId.value ? { employee_id: employeeId.value } : {}),
        ...(customerId.value ? { customer_id: customerId.value } : {}),
    };

    return route(
        props.tab === "sales"
            ? "reports.sales-pdf"
            : "reports.appointments-pdf",
        params,
    );
});
</script>

<template>
    <AppLayout>
        <Head title="Relatórios" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Relatórios</h1>
                    <p class="page-header-subtitle">
                        Desempenho de vendas e agendamentos.
                    </p>
                </div>
                <div class="page-header-actions">
                    <a :href="pdfHref" class="btn btn-secondary">
                        <Icon name="download" class="size-4" />
                        Exportar PDF
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex flex-col gap-3">
                    <div class="flex items-center gap-2">
                        <button
                            v-for="option in tabs"
                            :key="option.value"
                            type="button"
                            class="pill px-4 py-2 text-sm font-medium"
                            :class="{ 'pill-active': tab === option.value }"
                            @click="setTab(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <PeriodFilter
                            :period="filters.period"
                            :date-from="filters.date_from"
                            :date-to="filters.date_to"
                            @change="onPeriodChange"
                        />
                        <div class="flex flex-col gap-3 sm:flex-row lg:w-auto">
                            <div class="w-full sm:w-56">
                                <SearchSelect
                                    :model-value="employeeId"
                                    :options="employees"
                                    icon="briefcase"
                                    placeholder="Todos os Funcionários"
                                    @update:model-value="onEmployeeChange"
                                />
                            </div>
                            <div class="w-full sm:w-56">
                                <SearchSelect
                                    :model-value="customerId"
                                    :options="customers"
                                    icon="user"
                                    placeholder="Todos os Clientes"
                                    @update:model-value="onCustomerChange"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <template v-if="tab === 'sales' && sales">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="card p-5">
                        <p class="text-sm text-secondary">Total Faturado</p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ formatBRL(sales.summary.total) }}
                        </p>
                    </div>
                    <div class="card p-5">
                        <p class="text-sm text-secondary">
                            Quantidade de Vendas
                        </p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ sales.summary.count }}
                        </p>
                    </div>
                    <div class="card p-5">
                        <p class="text-sm text-secondary">Ticket Médio</p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{ formatBRL(sales.summary.average) }}
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Faturamento por Dia</h2>
                    </div>
                    <div class="card-body">
                        <BarChart :bars="sales.chart" currency />
                    </div>
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Vendas por Funcionário</h2>
                    </div>
                    <div v-if="sales.byEmployee.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                                >
                                    <th class="px-5 py-3 font-medium">
                                        Funcionário
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Vendas
                                    </th>
                                    <th class="px-5 py-3 font-medium">Itens</th>
                                    <th class="px-5 py-3 font-medium">Total</th>
                                    <th class="px-5 py-3 font-medium">
                                        Ticket Médio
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right font-medium"
                                    >
                                        % do Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in sales.byEmployee"
                                    :key="row.name"
                                    class="border-b border-border last:border-0"
                                >
                                    <td class="px-5 py-3 font-medium">
                                        {{ row.name }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.count }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.items }}
                                    </td>
                                    <td class="px-5 py-3">
                                        {{ formatBRL(row.total) }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ formatBRL(row.average) }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right text-secondary"
                                    >
                                        {{
                                            row.percent.toLocaleString("pt-BR")
                                        }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState
                        v-else
                        icon="shopping-cart"
                        title="Nenhuma Venda no Período"
                        description="Ajuste os filtros para visualizar os dados."
                    />
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Vendas por Produto e Serviço</h2>
                    </div>
                    <div v-if="sales.byProduct.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                                >
                                    <th class="px-5 py-3 font-medium">
                                        Produto
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Quantidade
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right font-medium"
                                    >
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in sales.byProduct"
                                    :key="row.name"
                                    class="border-b border-border last:border-0"
                                >
                                    <td class="px-5 py-3 font-medium">
                                        {{ row.name }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.quantity }}
                                    </td>
                                    <td class="px-5 py-3 text-right">
                                        {{ formatBRL(row.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState
                        v-else
                        icon="package"
                        title="Nenhum Item Vendido no Período"
                        description="Ajuste os filtros para visualizar os dados."
                    />
                </div>
            </template>

            <template v-if="tab === 'appointments' && appointments">
                <div
                    class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6"
                >
                    <div class="card p-5">
                        <p class="text-sm text-secondary">
                            Taxa de Comparecimento
                        </p>
                        <p class="mt-1 text-2xl font-semibold">
                            {{
                                formatRate(appointments.summary.attendance_rate)
                            }}
                        </p>
                    </div>
                    <div
                        v-for="status in appointments.summary.statuses"
                        :key="status.status"
                        class="card p-5"
                    >
                        <span
                            class="appt-badge"
                            :class="`appt-badge-${status.status.replace('_', '-')}`"
                        >
                            {{ status.label }}
                        </span>
                        <p class="mt-2 text-2xl font-semibold">
                            {{ status.count }}
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Agendamentos por Dia</h2>
                    </div>
                    <div class="card-body">
                        <BarChart :bars="appointments.chart" />
                    </div>
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Agendamentos por Funcionário</h2>
                    </div>
                    <div
                        v-if="appointments.byEmployee.length"
                        class="overflow-x-auto"
                    >
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                                >
                                    <th class="px-5 py-3 font-medium">
                                        Funcionário
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Agendados
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Confirmados
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Finalizados
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Cancelados
                                    </th>
                                    <th class="px-5 py-3 font-medium">
                                        Não Compareceu
                                    </th>
                                    <th
                                        class="px-5 py-3 text-right font-medium"
                                    >
                                        Taxa de Conclusão
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="row in appointments.byEmployee"
                                    :key="row.name"
                                    class="border-b border-border last:border-0"
                                >
                                    <td class="px-5 py-3 font-medium">
                                        {{ row.name }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.scheduled }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.confirmed }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.completed }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.cancelled }}
                                    </td>
                                    <td class="px-5 py-3 text-secondary">
                                        {{ row.no_show }}
                                    </td>
                                    <td
                                        class="px-5 py-3 text-right text-secondary"
                                    >
                                        {{ formatRate(row.completion_rate) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <EmptyState
                        v-else
                        icon="calendar"
                        title="Nenhum Agendamento no Período"
                        description="Ajuste os filtros para visualizar os dados."
                    />
                </div>
            </template>
        </div>
    </AppLayout>
</template>
