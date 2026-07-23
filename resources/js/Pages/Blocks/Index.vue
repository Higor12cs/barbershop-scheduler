<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import EmptyState from '../../Components/EmptyState.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import Pagination from '../../Components/Pagination.vue';
import SelectInput from '../../Components/SelectInput.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { parseISODate } from '../../Support/date.js';

const props = defineProps({
    blocks: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    employees: { type: Array, default: () => [] },
});

const employeeFilter = ref(props.filters.employee_id ?? '');
const confirming = ref(null);
const deleting = ref(false);

const dateFormatter = new Intl.DateTimeFormat('pt-BR');

function formatDate(value) {
    return dateFormatter.format(parseISODate(value));
}

function periodLabel(block) {
    const from = formatDate(block.start_date);
    const to = formatDate(block.end_date);

    if (block.all_day) {
        return from === to ? `${from} (dia inteiro)` : `${from} a ${to} (dias inteiros)`;
    }

    if (from === to) {
        return `${from}, ${block.start_time} às ${block.end_time}`;
    }

    return `${from} ${block.start_time} até ${to} ${block.end_time}`;
}

function onFilter(value) {
    employeeFilter.value = value;

    router.get(route('blocks.index'), value ? { employee_id: value } : {}, {
        preserveState: true,
        replace: true,
    });
}

function confirmDelete(block) {
    confirming.value = block;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route('blocks.destroy', confirming.value.id), {
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
        <Head title="Bloqueios" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Bloqueios de Agenda</h1>
                    <p class="page-header-subtitle">
                        Férias, folgas e feriados que impedem novos agendamentos.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('blocks.create')" class="btn btn-primary">
                        <Icon name="plus" class="size-4" />
                        Novo Bloqueio
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-end">
                    <div class="w-full sm:max-w-xs">
                        <SelectInput
                            :model-value="employeeFilter"
                            :options="[{ value: '', label: 'Todos os funcionários' }, ...employees]"
                            @update:model-value="onFilter"
                        />
                    </div>
                </div>

                <div v-if="blocks.data.length" class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted">
                                <th class="px-5 py-3 font-medium">Funcionário</th>
                                <th class="px-5 py-3 font-medium">Período</th>
                                <th class="px-5 py-3 font-medium">Motivo</th>
                                <th class="px-5 py-3 font-medium">Situação</th>
                                <th class="px-5 py-3 text-right font-medium">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="block in blocks.data" :key="block.id" class="border-b border-border last:border-0">
                                <td class="px-5 py-3 font-medium">
                                    <span v-if="block.employee_name" class="flex items-center gap-2">
                                        <span
                                            class="size-2.5 shrink-0 rounded-full"
                                            :style="{ backgroundColor: block.employee_color }"
                                        />
                                        {{ block.employee_name }}
                                    </span>
                                    <span v-else class="text-secondary">Todos os funcionários</span>
                                </td>
                                <td class="px-5 py-3 text-secondary">{{ periodLabel(block) }}</td>
                                <td class="px-5 py-3 text-secondary">{{ block.reason ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="badge" :class="block.is_past ? 'badge-muted' : 'badge-success'">
                                        {{ block.is_past ? 'Encerrado' : 'Vigente' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="route('blocks.edit', block.id)"
                                            class="rounded-lg border border-border p-2 text-secondary transition-colors hover:bg-surface-muted"
                                        >
                                            <Icon name="pencil" class="size-4" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                                            @click="confirmDelete(block)"
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
                    icon="calendar"
                    title="Nenhum Bloqueio Cadastrado"
                    description="Cadastre férias, folgas ou feriados para fechar a agenda nesses períodos."
                >
                    <Link :href="route('blocks.create')" class="btn btn-primary">
                        <Icon name="plus" class="size-4" />
                        Novo Bloqueio
                    </Link>
                </EmptyState>

                <div v-if="blocks.data.length" class="flex justify-end border-t border-border p-4">
                    <Pagination :links="blocks.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Bloqueio"
            message="Tem certeza que deseja remover este bloqueio? A agenda voltará a aceitar agendamentos no período."
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
