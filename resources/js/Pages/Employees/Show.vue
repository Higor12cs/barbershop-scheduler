<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { formatBRL } from '../../Support/money.js';
import { whatsappUrl } from '../../Support/whatsapp.js';

const props = defineProps({
    employee: { type: Object, required: true },
    stats: { type: Object, required: true },
    statusCounts: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    recentAppointments: { type: Array, default: () => [] },
    recentSales: { type: Array, default: () => [] },
});

const phoneUrl = computed(() => (props.employee.phone ? whatsappUrl(props.employee.phone) : null));

function badgeClass(status) {
    return `appt-badge appt-badge-${status.replace('_', '-')}`;
}

function formatRate(value) {
    return value === null ? '—' : `${value.toLocaleString('pt-BR')}%`;
}
</script>

<template>
    <AppLayout>
        <Head :title="employee.name" />

        <div class="space-y-6">
            <div class="page-header">
                <div class="flex items-center gap-3">
                    <Link :href="route('employees.index')" class="btn btn-secondary px-2">
                        <Icon name="chevron-left" class="size-5" />
                    </Link>
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full" :style="{ backgroundColor: employee.color }" />
                        <div>
                            <h1 class="page-header-title">{{ employee.name }}</h1>
                            <p class="page-header-subtitle">Perfil do profissional</p>
                        </div>
                    </div>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('employees.edit', employee.id)" class="btn btn-secondary">
                        <Icon name="pencil" class="size-4" />
                        Editar
                    </Link>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                    <span class="badge" :class="employee.active ? 'badge-success' : 'badge-muted'">
                        {{ employee.active ? 'Ativo' : 'Inativo' }}
                    </span>
                    <a
                        v-if="phoneUrl"
                        :href="phoneUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-success hover:underline"
                    >
                        <Icon name="message-circle" class="size-4" />
                        {{ employee.phone }}
                    </a>
                    <span v-if="employee.email" class="text-secondary">{{ employee.email }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="card p-5">
                    <p class="text-sm text-secondary">Faturamento</p>
                    <p class="mt-1 text-2xl font-semibold">{{ formatBRL(stats.revenue) }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Atendimentos</p>
                    <p class="mt-1 text-2xl font-semibold">{{ stats.completed }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Ticket Médio</p>
                    <p class="mt-1 text-2xl font-semibold">{{ formatBRL(stats.average_ticket) }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Comparecimento</p>
                    <p class="mt-1 text-2xl font-semibold">{{ formatRate(stats.attendance_rate) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="card flex items-center gap-3 p-5">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-surface-muted text-secondary">
                        <Icon name="scissors" class="size-5" />
                    </span>
                    <div>
                        <p class="text-sm text-secondary">Serviço Mais Realizado</p>
                        <p class="font-medium">{{ stats.top_service ?? '—' }}</p>
                    </div>
                </div>
                <div class="card flex items-center gap-3 p-5">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-surface-muted text-secondary">
                        <Icon name="calendar" class="size-5" />
                    </span>
                    <div>
                        <p class="text-sm text-secondary">Próximos Agendamentos</p>
                        <p class="font-medium">{{ stats.upcoming }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Próximos Agendamentos</h2>
                    </div>
                    <div v-if="upcoming.length" class="divide-y divide-border">
                        <div v-for="item in upcoming" :key="item.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ item.customer }}</p>
                                <p class="text-xs text-secondary">{{ item.date }} · {{ item.time }} · {{ item.service }}</p>
                            </div>
                            <span :class="badgeClass(item.status)">{{ item.status_label }}</span>
                        </div>
                    </div>
                    <EmptyState v-else icon="calendar" title="Nenhum Agendamento Futuro" description="Sem horários marcados para este profissional." />
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Últimas Vendas</h2>
                    </div>
                    <div v-if="recentSales.length" class="divide-y divide-border">
                        <div v-for="sale in recentSales" :key="sale.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <div>
                                <p class="font-medium">{{ sale.customer ?? 'Cliente' }}</p>
                                <p class="text-xs text-secondary">{{ sale.date }}</p>
                            </div>
                            <span class="font-medium">{{ formatBRL(sale.total) }}</span>
                        </div>
                    </div>
                    <EmptyState v-else icon="shopping-cart" title="Nenhuma Venda" description="Este profissional ainda não registrou vendas." />
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">Histórico de Agendamentos</h2>
                </div>
                <div v-if="recentAppointments.length" class="divide-y divide-border">
                    <div v-for="item in recentAppointments" :key="item.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ item.customer }}</p>
                            <p class="text-xs text-secondary">{{ item.date }} · {{ item.time }} · {{ item.service }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-secondary">{{ formatBRL(item.price) }}</span>
                            <span :class="badgeClass(item.status)">{{ item.status_label }}</span>
                        </div>
                    </div>
                </div>
                <EmptyState v-else icon="calendar" title="Sem Histórico" description="Este profissional ainda não teve atendimentos." />
            </div>
        </div>
    </AppLayout>
</template>
