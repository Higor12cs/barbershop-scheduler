<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import { route } from "ziggy-js";
import Icon from "../../Components/Icon.vue";
import EmptyState from "../../Components/EmptyState.vue";
import ConfirmDialog from "../../Components/ConfirmDialog.vue";
import Pagination from "../../Components/Pagination.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import { parseISODate } from "../../Support/date.js";

const props = defineProps({
    recurrences: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? "");
const confirming = ref(null);
const deleting = ref(false);

let searchTimeout = null;

const dateFormatter = new Intl.DateTimeFormat("pt-BR");

function formatDate(value) {
    return value ? dateFormatter.format(parseISODate(value)) : "—";
}

function intervalLabel(days) {
    if (days === 7) {
        return "Semanal";
    }

    if (days === 14) {
        return "Quinzenal";
    }

    if (days === 30) {
        return "Mensal";
    }

    return `A cada ${days} dias`;
}

function reload() {
    const params = {};

    if (search.value) {
        params.search = search.value;
    }

    router.get(route("recurrences.index"), params, {
        preserveState: true,
        replace: true,
    });
}

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(reload, 350);
});

function confirmDelete(recurrence) {
    confirming.value = recurrence;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("recurrences.destroy", confirming.value.id), {
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
        <Head title="Recorrências" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Recorrências</h1>
                    <p class="page-header-subtitle">
                        Gerencie os atendimentos recorrentes da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('recurrences.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Nova Recorrência
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div
                    class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-end"
                >
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

                <div v-if="recurrences.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr
                                class="border-b border-border text-left text-xs uppercase tracking-wide text-muted"
                            >
                                <th class="px-5 py-3 font-medium">Cliente</th>
                                <th class="px-5 py-3 font-medium">Serviço</th>
                                <th class="px-5 py-3 font-medium">
                                    Funcionário
                                </th>
                                <th class="px-5 py-3 font-medium">Intervalo</th>
                                <th class="px-5 py-3 font-medium">Horário</th>
                                <th class="px-5 py-3 font-medium">Início</th>
                                <th class="px-5 py-3 font-medium">Término</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="recurrence in recurrences.data"
                                :key="recurrence.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3 font-medium">
                                    {{ recurrence.customer }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ recurrence.service }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ recurrence.employee }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{
                                        intervalLabel(recurrence.interval_days)
                                    }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ recurrence.time }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ formatDate(recurrence.starts_on) }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{
                                        recurrence.ends_on
                                            ? formatDate(recurrence.ends_on)
                                            : "Sem limite"
                                    }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="badge"
                                        :class="
                                            recurrence.active
                                                ? 'badge-success'
                                                : 'badge-danger'
                                        "
                                    >
                                        {{
                                            recurrence.active
                                                ? "Ativa"
                                                : "Inativa"
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
                                                    'recurrences.edit',
                                                    recurrence.id,
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
                                            @click="confirmDelete(recurrence)"
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
                    icon="repeat"
                    title="Nenhuma Recorrência Encontrada"
                    :description="
                        search
                            ? 'Nenhuma recorrência corresponde à busca.'
                            : 'Cadastre a primeira recorrência para começar.'
                    "
                >
                    <Link
                        v-if="!search"
                        :href="route('recurrences.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Nova Recorrência
                    </Link>
                </EmptyState>

                <div
                    v-if="recurrences.data.length"
                    class="flex justify-end border-t border-border p-4"
                >
                    <Pagination :links="recurrences.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Recorrência"
            message="Tem certeza que deseja remover esta recorrência? Os agendamentos futuros ainda não atendidos também serão removidos."
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
