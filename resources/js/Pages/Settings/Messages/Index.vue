<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import FormField from "../../../Components/FormField.vue";
import TextInput from "../../../Components/TextInput.vue";
import CheckboxInput from "../../../Components/CheckboxInput.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    booking_enabled: props.settings.booking_enabled,
    booking_template: props.settings.booking_template ?? "",
    reminder_enabled: props.settings.reminder_enabled,
    reminder_minutes_before: props.settings.reminder_minutes_before,
    reminder_template: props.settings.reminder_template ?? "",
    confirmation_enabled: props.settings.confirmation_enabled,
    confirmation_minutes_before: props.settings.confirmation_minutes_before,
    confirmation_template: props.settings.confirmation_template ?? "",
    recurrence_horizon_days: props.settings.recurrence_horizon_days,
});

const legend = {
    "{cliente}": "Nome do cliente",
    "{funcionario}": "Nome do funcionário",
    "{servico}": "Serviço agendado",
    "{data}": "Data do agendamento",
    "{hora}": "Horário do agendamento",
    "{estabelecimento}": "Nome do estabelecimento",
    "{link_calendario}": "Link para adicionar ao calendário",
};

const commonPlaceholders = [
    "{cliente}",
    "{funcionario}",
    "{servico}",
    "{data}",
    "{hora}",
    "{estabelecimento}",
];

const bookingPlaceholders = [...commonPlaceholders, "{link_calendario}"];

const minutePresets = [
    { label: "1 hora", value: 60 },
    { label: "2 horas", value: 120 },
    { label: "3 horas", value: 180 },
    { label: "1 dia", value: 1440 },
];

const messages = [
    {
        key: "booking",
        label: "Agendamento",
        description: "Enviada logo após criar um novo agendamento.",
        enabled: "booking_enabled",
        template: "booking_template",
        minutes: null,
        minutesHint: null,
        placeholders: bookingPlaceholders,
        note: null,
    },
    {
        key: "reminder",
        label: "Lembrete",
        description: "Enviada antes do horário como lembrete do atendimento.",
        enabled: "reminder_enabled",
        template: "reminder_template",
        minutes: "reminder_minutes_before",
        minutesHint: "Quantos minutos antes do horário o lembrete será enviado.",
        placeholders: commonPlaceholders,
        note: null,
    },
    {
        key: "confirmation",
        label: "Confirmação",
        description: "Enviada antes do horário pedindo a confirmação do cliente.",
        enabled: "confirmation_enabled",
        template: "confirmation_template",
        minutes: "confirmation_minutes_before",
        minutesHint: "Quantos minutos antes do horário a confirmação será enviada.",
        placeholders: commonPlaceholders,
        note: 'Enviada com os botões "Confirmar" e "Cancelar" para o cliente responder.',
    },
];

const activeTab = ref("booking");

const textareas = {};

function setRef(field, el) {
    textareas[field] = el;
}

function insertPlaceholder(field, placeholder) {
    const el = textareas[field];
    const value = form[field] ?? "";

    if (!el) {
        form[field] = value + placeholder;
        return;
    }

    const start = el.selectionStart ?? value.length;
    const end = el.selectionEnd ?? start;
    form[field] = value.slice(0, start) + placeholder + value.slice(end);

    nextTick(() => {
        el.focus();
        const pos = start + placeholder.length;
        el.setSelectionRange(pos, pos);
    });
}

function submit() {
    form.put(route("settings.messages.update"), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Configurações" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Modelos de Mensagens</h1>
                    <p class="page-header-subtitle">
                        Configure as mensagens automáticas enviadas pelo
                        WhatsApp.
                    </p>
                </div>
                <div class="page-header-actions">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <Icon name="check" class="size-4" />
                        {{
                            form.processing
                                ? "Salvando..."
                                : "Salvar Configurações"
                        }}
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body space-y-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="message in messages"
                            :key="message.key"
                            type="button"
                            class="pill px-4 py-2 text-sm font-medium"
                            :class="{ 'pill-active': activeTab === message.key }"
                            @click="activeTab = message.key"
                        >
                            {{ message.label }}
                        </button>
                    </div>

                    <div
                        v-for="message in messages"
                        v-show="activeTab === message.key"
                        :key="message.key"
                        class="space-y-4"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm text-secondary">
                                {{ message.description }}
                            </p>
                            <CheckboxInput
                                v-model="form[message.enabled]"
                                label="Ativar"
                            />
                        </div>

                        <div v-if="message.minutes" class="space-y-2">
                            <FormField
                                label="Antecedência (Minutos)"
                                :error="form.errors[message.minutes]"
                                :hint="message.minutesHint"
                            >
                                <TextInput
                                    v-model="form[message.minutes]"
                                    type="number"
                                    class="max-w-40"
                                />
                            </FormField>

                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="preset in minutePresets"
                                    :key="preset.value"
                                    type="button"
                                    class="pill px-3 py-1.5 text-xs font-medium"
                                    :class="{
                                        'pill-active':
                                            Number(form[message.minutes]) ===
                                            preset.value,
                                    }"
                                    @click="form[message.minutes] = preset.value"
                                >
                                    {{ preset.label }}
                                </button>
                            </div>
                        </div>

                        <FormField
                            label="Mensagem"
                            :error="form.errors[message.template]"
                        >
                            <textarea
                                :ref="(el) => setRef(message.template, el)"
                                v-model="form[message.template]"
                                class="form-control form-control-textarea"
                                rows="4"
                            />
                        </FormField>

                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="placeholder in message.placeholders"
                                :key="placeholder"
                                type="button"
                                class="badge badge-muted transition-colors hover:bg-surface-muted"
                                @click="
                                    insertPlaceholder(
                                        message.template,
                                        placeholder,
                                    )
                                "
                            >
                                {{ placeholder }}
                            </button>
                        </div>

                        <p v-if="message.note" class="text-xs text-secondary">
                            {{ message.note }}
                        </p>

                        <ul class="space-y-1 text-xs text-secondary">
                            <li
                                v-for="placeholder in message.placeholders"
                                :key="placeholder"
                            >
                                <span class="font-mono text-foreground">{{
                                    placeholder
                                }}</span>
                                — {{ legend[placeholder] }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Geral</h2>
                </div>
                <div class="card-body">
                    <FormField
                        label="Horizonte de Recorrências (Dias)"
                        :error="form.errors.recurrence_horizon_days"
                        hint="Período à frente para o qual os agendamentos recorrentes são gerados."
                    >
                        <TextInput
                            v-model="form.recurrence_horizon_days"
                            type="number"
                            class="max-w-40"
                        />
                    </FormField>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
