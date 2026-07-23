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
    employees: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search ?? "");
const confirming = ref(null);
const deleting = ref(false);

let searchTimeout = null;

watch(search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route("employees.index"), value ? { search: value } : {}, {
            preserveState: true,
            replace: true,
        });
    }, 350);
});

function confirmDelete(employee) {
    confirming.value = employee;
}

function destroy() {
    if (!confirming.value) {
        return;
    }

    router.delete(route("employees.destroy", confirming.value.id), {
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
        <Head title="Funcionários" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Cadastro de Funcionários</h1>
                    <p class="page-header-subtitle">
                        Gerencie a equipe da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('employees.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Funcionário
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

                <div v-if="employees.data.length" class="overflow-x-auto">
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
                                v-for="employee in employees.data"
                                :key="employee.id"
                                class="border-b border-border last:border-0"
                            >
                                <td class="px-5 py-3">
                                    <Link
                                        :href="route('employees.show', employee.id)"
                                        class="flex items-center gap-2 font-medium transition-colors hover:text-primary"
                                    >
                                        <span
                                            class="size-3 shrink-0 rounded-full"
                                            :style="{
                                                backgroundColor: employee.color,
                                            }"
                                        />
                                        {{ employee.name }}
                                    </Link>
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ employee.phone ?? "—" }}
                                </td>
                                <td class="px-5 py-3 text-secondary">
                                    {{ employee.email ?? "—" }}
                                </td>
                                <td class="px-5 py-3">
                                    <span
                                        class="badge"
                                        :class="
                                            employee.active
                                                ? 'badge-success'
                                                : 'badge-danger'
                                        "
                                    >
                                        {{
                                            employee.active
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
                                                    'employees.schedule.edit',
                                                    employee.id,
                                                )
                                            "
                                            class="rounded-lg border border-border p-2 text-secondary transition-colors hover:bg-surface-muted"
                                            title="Jornada de trabalho"
                                        >
                                            <Icon name="clock" class="size-4" />
                                        </Link>
                                        <Link
                                            :href="
                                                route(
                                                    'employees.edit',
                                                    employee.id,
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
                                            @click="confirmDelete(employee)"
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
                    icon="briefcase"
                    title="Nenhum Funcionário Encontrado"
                    :description="
                        search
                            ? 'Nenhum funcionário corresponde à busca realizada.'
                            : 'Cadastre o primeiro funcionário para começar.'
                    "
                >
                    <Link
                        v-if="!search"
                        :href="route('employees.create')"
                        class="btn btn-primary"
                    >
                        <Icon name="plus" class="size-4" />
                        Novo Funcionário
                    </Link>
                </EmptyState>

                <div
                    v-if="employees.data.length"
                    class="flex justify-end border-t border-border p-4"
                >
                    <Pagination :links="employees.links" />
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming !== null"
            title="Remover Funcionário"
            :message="`Tem certeza que deseja remover o funcionário ${confirming?.name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = null"
        />
    </AppLayout>
</template>
