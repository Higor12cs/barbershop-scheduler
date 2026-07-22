<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';
import Icon from '../../Components/Icon.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import { formatBRL } from '../../Support/money.js';

const props = defineProps({
    sale: { type: Object, required: true },
});

const confirming = ref(false);
const deleting = ref(false);

function destroy() {
    router.delete(route('sales.destroy', props.sale.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = false;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Venda #${sale.id}`" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Venda #{{ sale.id }}</h1>
                    <p class="page-header-subtitle">Registrada em {{ sale.sold_at }}</p>
                </div>
                <div class="page-header-actions">
                    <Link :href="route('sales.index')" class="btn btn-secondary">Voltar</Link>
                    <button type="button" class="btn btn-danger" @click="confirming = true">
                        <Icon name="trash" class="size-4" />
                        Excluir Venda
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="card-title">Dados da Venda</h2>
                    <span class="badge" :class="sale.from_appointment ? 'badge-info' : ''">
                        {{ sale.from_appointment ? 'Agendamento' : 'Manual' }}
                    </span>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <Icon name="user" class="size-4 text-muted" />
                        <span class="font-medium">{{ sale.customer_name }}</span>
                        <span v-if="sale.customer_phone" class="text-secondary">· {{ sale.customer_phone }}</span>
                    </div>
                    <div v-if="sale.employee_name" class="flex items-center gap-2">
                        <span class="size-3 rounded-full" :style="{ backgroundColor: sale.employee_color }" />
                        <span>{{ sale.employee_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Icon name="clock" class="size-4 text-muted" />
                        <span>{{ sale.sold_at }}</span>
                    </div>
                    <div v-if="sale.from_appointment && sale.appointment_date" class="flex items-center gap-2">
                        <Icon name="calendar" class="size-4 text-muted" />
                        <Link
                            :href="route('appointments.index', { date: sale.appointment_date })"
                            class="font-medium underline underline-offset-2 hover:text-secondary"
                        >
                            Ver Agendamento de Origem
                        </Link>
                    </div>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <h2 class="card-title">Itens</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border text-left text-xs uppercase tracking-wide text-muted">
                                <th class="px-5 py-3 font-medium">Descrição</th>
                                <th class="px-5 py-3 font-medium">Quantidade</th>
                                <th class="px-5 py-3 font-medium">Preço Unitário</th>
                                <th class="px-5 py-3 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in sale.items" :key="item.id" class="border-b border-border last:border-0">
                                <td class="px-5 py-3 font-medium">{{ item.description }}</td>
                                <td class="px-5 py-3 text-secondary">{{ item.quantity }}</td>
                                <td class="px-5 py-3 text-secondary">{{ formatBRL(item.unit_price) }}</td>
                                <td class="px-5 py-3 text-right">{{ formatBRL(item.total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-border px-5 py-4">
                    <span class="text-sm text-secondary">Total da Venda</span>
                    <span class="text-2xl font-semibold">{{ formatBRL(sale.total) }}</span>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :show="confirming"
            title="Remover Venda"
            :message="`Tem certeza que deseja remover a venda de ${sale.customer_name}?`"
            confirm-label="Remover"
            :processing="deleting"
            @confirm="destroy"
            @cancel="confirming = false"
        />
    </AppLayout>
</template>
