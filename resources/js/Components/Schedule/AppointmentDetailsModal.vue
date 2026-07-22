<script setup>
import { computed } from 'vue';
import Modal from '../Modal.vue';
import Icon from '../Icon.vue';
import { formatBRL } from '../../Support/money.js';
import { formatLongDate } from '../../Support/date.js';
import { whatsappUrl } from '../../Support/whatsapp.js';

const props = defineProps({
    show: { type: Boolean, default: false },
    appointment: { type: Object, default: null },
    whatsappReady: { type: Boolean, default: false },
    messageTypes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'status', 'complete', 'edit', 'delete', 'send']);

const badgeClass = computed(() =>
    props.appointment ? `appt-badge appt-badge-${props.appointment.status.replace('_', '-')}` : '',
);

const canEdit = computed(() => props.appointment && ['scheduled', 'confirmed'].includes(props.appointment.status));
const canConfirm = computed(() => props.appointment?.status === 'scheduled');
const canComplete = computed(() => props.appointment && ['scheduled', 'confirmed'].includes(props.appointment.status));
const canNoShow = computed(() => props.appointment && ['scheduled', 'confirmed'].includes(props.appointment.status));
const canCancel = computed(() => props.appointment && ['scheduled', 'confirmed'].includes(props.appointment.status));
const canReopen = computed(() => props.appointment && ['completed', 'cancelled', 'no_show'].includes(props.appointment.status));

const phoneUrl = computed(() => (props.appointment?.customer_phone ? whatsappUrl(props.appointment.customer_phone) : null));

const statusLabels = {
    sent: 'Enviada',
    failed: 'Falha no envio',
    queued: 'Na fila',
};
</script>

<template>
    <Modal :show="show" title="Detalhes do Agendamento" max-width="3xl" @close="emit('close')">
        <div v-if="appointment" class="grid gap-5 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span :class="badgeClass">{{ appointment.status_label }}</span>
                        <span v-if="appointment.origin === 'recurrence'" class="badge badge-recurrence">Recorrência</span>
                    </div>
                    <span class="text-lg font-semibold">{{ formatBRL(appointment.price) }}</span>
                </div>

                <div
                    v-if="appointment.origin === 'recurrence'"
                    class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                    <Icon name="repeat" class="size-4 shrink-0" />
                    <span>Gerado automaticamente por uma recorrência.</span>
                </div>

                <div class="space-y-3 rounded-xl border border-border bg-surface-alt p-4 text-sm">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <Icon name="user" class="size-4 text-muted" />
                        <span class="font-medium">{{ appointment.customer_name }}</span>
                        <a
                            v-if="phoneUrl"
                            :href="phoneUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-1 text-success hover:underline"
                        >
                            <Icon name="message-circle" class="size-3.5" />
                            {{ appointment.customer_phone }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <Icon name="scissors" class="size-4 text-muted" />
                        <span>{{ appointment.product_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full" :style="{ backgroundColor: appointment.employee_color }" />
                        <span>{{ appointment.employee_name }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Icon name="clock" class="size-4 text-muted" />
                        <span>{{ formatLongDate(appointment.date) }} · {{ appointment.start_time }} às {{ appointment.end_time }}</span>
                    </div>
                </div>

                <div v-if="appointment.notes" class="rounded-xl border border-border p-4 text-sm text-secondary">
                    {{ appointment.notes }}
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <button v-if="canConfirm" type="button" class="btn btn-secondary" @click="emit('status', 'confirmed')">
                        <Icon name="check" class="size-4" />
                        Confirmar
                    </button>
                    <button v-if="canComplete" type="button" class="btn btn-primary" @click="emit('complete')">
                        <Icon name="check-check" class="size-4" />
                        Finalizar Atendimento
                    </button>
                    <button v-if="canNoShow" type="button" class="btn btn-secondary" @click="emit('status', 'no_show')">
                        <Icon name="user" class="size-4" />
                        Não Compareceu
                    </button>
                    <button v-if="canCancel" type="button" class="btn btn-secondary" @click="emit('status', 'cancelled')">
                        <Icon name="x-circle" class="size-4" />
                        Cancelar
                    </button>
                    <button v-if="canReopen" type="button" class="btn btn-secondary" @click="emit('status', 'scheduled')">
                        <Icon name="rotate-ccw" class="size-4" />
                        Reabrir
                    </button>
                    <button v-if="canEdit" type="button" class="btn btn-secondary" @click="emit('edit')">
                        <Icon name="pencil" class="size-4" />
                        Editar
                    </button>
                    <button type="button" class="btn btn-danger" @click="emit('delete')">
                        <Icon name="trash" class="size-4" />
                        Excluir
                    </button>
                </div>
            </div>

            <div class="space-y-3 border-t border-border pt-4 lg:border-l lg:border-t-0 lg:pt-0 lg:pl-5">
                <h3 class="text-sm font-semibold">Mensagens</h3>

                <div v-if="appointment.notifications.length" class="space-y-2">
                    <div
                        v-for="(message, index) in appointment.notifications"
                        :key="`${message.type}-${index}`"
                        class="flex items-start gap-2 rounded-lg border border-border p-2 text-xs"
                    >
                        <Icon
                            :name="message.status === 'sent' ? 'check-circle' : 'alert-triangle'"
                            class="mt-0.5 size-4 shrink-0"
                            :class="message.status === 'sent' ? 'text-success' : 'text-danger'"
                        />
                        <div class="min-w-0">
                            <p class="font-medium text-foreground">{{ message.type_label }}</p>
                            <p class="text-muted">{{ message.sent_at ?? statusLabels[message.status] }}</p>
                            <p v-if="message.error" class="text-danger">{{ message.error }}</p>
                        </div>
                    </div>
                </div>

                <p v-else class="text-xs text-muted">Nenhuma mensagem enviada ainda.</p>

                <div class="space-y-2 border-t border-border pt-3">
                    <p class="text-xs font-medium text-secondary">Enviar manualmente</p>
                    <template v-if="whatsappReady">
                        <button
                            v-for="type in messageTypes"
                            :key="type.value"
                            type="button"
                            class="btn btn-secondary btn-block justify-start text-xs"
                            @click="emit('send', type.value)"
                        >
                            <Icon name="message-circle" class="size-4" />
                            Enviar {{ type.label }}
                        </button>
                    </template>
                    <p v-else class="text-xs text-muted">Configure o WhatsApp para enviar mensagens.</p>
                </div>
            </div>
        </div>
    </Modal>
</template>
