<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";
import Icon from "../Components/Icon.vue";
import BarChart from "../Components/Charts/BarChart.vue";
import AppLayout from "../Layouts/AppLayout.vue";
import { formatBRL } from "../Support/money.js";

defineProps({
    metrics: { type: Object, required: true },
    todaySchedule: { type: Array, default: () => [] },
    revenueChart: { type: Array, default: null },
    birthdays: { type: Array, default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <AppLayout>
        <Head title="Painel" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">
                        Bem-vindo, {{ user?.name }}
                    </h1>
                    <p class="page-header-subtitle">
                        Resumo do dia e do mês da barbearia.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="card p-5">
                    <p class="text-sm text-secondary">Agendamentos de Hoje</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ metrics.today_total }}
                    </p>
                    <p class="mt-1 text-sm text-secondary">
                        {{ metrics.today_completed }} finalizados
                    </p>
                </div>
                <div v-if="metrics.month_revenue !== null" class="card p-5">
                    <p class="text-sm text-secondary">Faturamento do Mês</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ formatBRL(metrics.month_revenue) }}
                    </p>
                    <p class="mt-1 text-sm text-secondary">
                        Vendas do mês corrente
                    </p>
                </div>
                <div v-if="metrics.forecast !== null" class="card p-5">
                    <p class="text-sm text-secondary">
                        Previsão de Faturamento
                    </p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ formatBRL(metrics.forecast) }}
                    </p>
                    <p class="mt-1 text-sm text-secondary">
                        Próximos 30 dias com base na agenda
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Novos Clientes no Mês</p>
                    <p class="mt-1 text-2xl font-semibold">
                        {{ metrics.new_customers }}
                    </p>
                    <p class="mt-1 text-sm text-secondary">
                        Cadastros no mês corrente
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 xl:grid-cols-3">
                <div class="card xl:col-span-2">
                    <div class="card-header flex items-center justify-between">
                        <h2 class="card-title">Agenda de Hoje</h2>
                        <Link
                            :href="route('appointments.index')"
                            class="flex items-center gap-1 text-sm font-medium text-secondary transition-colors hover:text-foreground"
                        >
                            Ver Agenda
                            <Icon name="arrow-right" class="size-4" />
                        </Link>
                    </div>
                    <div
                        v-if="todaySchedule.length"
                        class="divide-y divide-border"
                    >
                        <div
                            v-for="appointment in todaySchedule"
                            :key="appointment.id"
                            class="flex items-center gap-6 px-5 py-3 text-sm"
                        >
                            <span class="font-medium">{{
                                appointment.time
                            }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ appointment.customer_name }}
                                </p>
                                <p class="truncate text-secondary">
                                    {{ appointment.product_name }}
                                </p>
                            </div>
                            <span
                                class="flex items-center gap-1.5 text-secondary"
                            >
                                <span
                                    class="size-2.5 rounded-full"
                                    :style="{
                                        backgroundColor:
                                            appointment.employee_color,
                                    }"
                                />
                                <span class="hidden sm:inline">{{
                                    appointment.employee_name
                                }}</span>
                            </span>
                            <span
                                class="appt-badge"
                                :class="`appt-badge-${appointment.status.replace('_', '-')}`"
                            >
                                {{ appointment.status_label }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 px-6 py-10 text-center"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-surface-muted text-secondary"
                        >
                            <Icon name="calendar" class="size-5" />
                        </span>
                        <p class="text-sm text-secondary">
                            Nenhum agendamento restante hoje.
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Aniversariantes do Mês</h2>
                    </div>
                    <div v-if="birthdays.length" class="divide-y divide-border">
                        <div
                            v-for="birthday in birthdays"
                            :key="birthday.id"
                            class="flex items-center justify-between gap-3 px-5 py-3 text-sm"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <Icon
                                    name="cake"
                                    class="size-4 shrink-0 text-muted"
                                />
                                <span class="truncate font-medium">{{
                                    birthday.name
                                }}</span>
                            </span>
                            <span class="badge">{{ birthday.day }}</span>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center gap-2 px-6 py-10 text-center"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-full bg-surface-muted text-secondary"
                        >
                            <Icon name="cake" class="size-5" />
                        </span>
                        <p class="text-sm text-secondary">
                            Nenhum aniversariante este mês.
                        </p>
                    </div>
                </div>
            </div>

            <div v-if="revenueChart" class="card">
                <div class="card-header">
                    <h2 class="card-title">Faturamento dos Últimos 14 Dias</h2>
                </div>
                <div class="card-body">
                    <BarChart :bars="revenueChart" currency />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
