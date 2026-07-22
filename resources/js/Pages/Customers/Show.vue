<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '../../Layouts/AppLayout.vue';
import Icon from '../../Components/Icon.vue';
import EmptyState from '../../Components/EmptyState.vue';
import { formatBRL } from '../../Support/money.js';
import { whatsappUrl } from '../../Support/whatsapp.js';
import { birthdayProximity, formatDayMonth } from '../../Support/date.js';

const props = defineProps({
    customer: { type: Object, required: true },
    stats: { type: Object, required: true },
    statusCounts: { type: Array, default: () => [] },
    upcoming: { type: Array, default: () => [] },
    recentAppointments: { type: Array, default: () => [] },
    recentSales: { type: Array, default: () => [] },
});

const phoneUrl = computed(() => (props.customer.phone ? whatsappUrl(props.customer.phone) : null));

const birthdayLabels = {
    today: 'hoje',
    week: 'esta semana',
    month: 'este mês',
};

const birthday = computed(() => {
    if (!props.customer.birth_date) {
        return null;
    }

    const proximity = birthdayProximity(props.customer.birth_date);
    const date = formatDayMonth(props.customer.birth_date);

    return proximity ? `${date} · ${birthdayLabels[proximity]}` : date;
});

const isBirthdaySoon = computed(() => birthdayProximity(props.customer.birth_date) !== null);

function badgeClass(status) {
    return `appt-badge appt-badge-${status.replace('_', '-')}`;
}

function formatRate(value) {
    return value === null ? '—' : `${value.toLocaleString('pt-BR')}%`;
}
</script>

<template>
    <AppLayout>
        <Head :title="customer.name" />

        <div class="space-y-6">
            <div class="page-header">
                <div class="flex items-center gap-3">
                    <Link :href="route('customers.index')" class="btn btn-secondary px-2">
                        <Icon name="chevron-left" class="size-5" />
                    </Link>
                    <div>
                        <h1 class="page-header-title">{{ customer.name }}</h1>
                        <p class="page-header-subtitle">Perfil do cliente</p>
                    </div>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('customers.edit', customer.id)" class="btn btn-secondary">
                        <Icon name="pencil" class="size-4" />
                        Editar
                    </Link>
                </div>
            </div>

            <div class="card">
                <div class="card-body flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                    <span class="badge" :class="customer.active ? 'badge-success' : 'badge-muted'">
                        {{ customer.active ? 'Ativo' : 'Inativo' }}
                    </span>
                    <a
                        v-if="phoneUrl"
                        :href="phoneUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-success hover:underline"
                    >
                        <Icon name="message-circle" class="size-4" />
                        {{ customer.phone }}
                    </a>
                    <span v-if="customer.email" class="text-secondary">{{ customer.email }}</span>
                    <span
                        v-if="birthday"
                        class="inline-flex items-center gap-1.5"
                        :class="isBirthdaySoon ? 'text-amber-600' : 'text-secondary'"
                    >
                        <Icon name="cake" class="size-4" />
                        {{ birthday }}
                    </span>
                </div>
                <div v-if="customer.notes" class="border-t border-border px-5 py-4 text-sm text-secondary">
                    {{ customer.notes }}
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <div class="card p-5">
                    <p class="text-sm text-secondary">Total Gasto</p>
                    <p class="mt-1 text-2xl font-semibold">{{ formatBRL(stats.total_spent) }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-sm text-secondary">Visitas</p>
                    <p class="mt-1 text-2xl font-semibold">{{ stats.visits }}</p>
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
                        <p class="text-sm text-secondary">Serviço Favorito</p>
                        <p class="font-medium">{{ stats.favorite_service ?? '—' }}</p>
                    </div>
                </div>
                <div class="card flex items-center gap-3 p-5">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-surface-muted text-secondary">
                        <Icon name="clock" class="size-5" />
                    </span>
                    <div>
                        <p class="text-sm text-secondary">Última Visita</p>
                        <p class="font-medium">{{ stats.last_visit ?? 'Nenhuma ainda' }}</p>
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
                                <p class="font-medium">{{ item.service }}</p>
                                <p class="text-xs text-secondary">{{ item.date }} · {{ item.time }} · {{ item.employee }}</p>
                            </div>
                            <span :class="badgeClass(item.status)">{{ item.status_label }}</span>
                        </div>
                    </div>
                    <EmptyState v-else icon="calendar" title="Nenhum Agendamento Futuro" description="Este cliente não tem horários marcados." />
                </div>

                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h2 class="card-title">Últimas Vendas</h2>
                    </div>
                    <div v-if="recentSales.length" class="divide-y divide-border">
                        <div v-for="sale in recentSales" :key="sale.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                            <span class="text-secondary">{{ sale.date }}</span>
                            <span class="font-medium">{{ formatBRL(sale.total) }}</span>
                        </div>
                    </div>
                    <EmptyState v-else icon="shopping-cart" title="Nenhuma Venda" description="Este cliente ainda não tem vendas." />
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">Histórico de Agendamentos</h2>
                </div>
                <div v-if="recentAppointments.length" class="divide-y divide-border">
                    <div v-for="item in recentAppointments" :key="item.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="size-2.5 rounded-full" :style="{ backgroundColor: item.employee_color }" />
                            <div>
                                <p class="font-medium">{{ item.service }}</p>
                                <p class="text-xs text-secondary">{{ item.date }} · {{ item.time }} · {{ item.employee }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-secondary">{{ formatBRL(item.price) }}</span>
                            <span :class="badgeClass(item.status)">{{ item.status_label }}</span>
                        </div>
                    </div>
                </div>
                <EmptyState v-else icon="calendar" title="Sem Histórico" description="Este cliente ainda não teve atendimentos." />
            </div>
        </div>
    </AppLayout>
</template>
