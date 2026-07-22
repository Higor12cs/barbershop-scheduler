<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed, nextTick, ref } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import FormField from "../../../Components/FormField.vue";
import TextInput from "../../../Components/TextInput.vue";
import CheckboxInput from "../../../Components/CheckboxInput.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

const props = defineProps({
    type: { type: String, required: true },
    settings: { type: Object, required: true },
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

const types = {
    booking: {
        label: "Agendamento",
        description:
            "Oferecida para envio logo após criar um novo agendamento.",
        placeholders: [...commonPlaceholders, "{link_calendario}"],
        minutesHint: null,
        note: "Esta mensagem nunca é enviada sozinha: ao criar um agendamento, aparece uma confirmação perguntando se deseja enviá-la. Desativando, a pergunta deixa de aparecer — você ainda pode enviá-la a qualquer momento pelos detalhes do agendamento.",
    },
    reminder: {
        label: "Lembrete",
        description: "Enviada antes do horário como lembrete do atendimento.",
        placeholders: commonPlaceholders,
        minutesHint: "Quantos minutos antes do horário o lembrete será enviado.",
        note: null,
    },
    confirmation: {
        label: "Confirmação",
        description: "Enviada antes do horário pedindo a confirmação do cliente.",
        placeholders: commonPlaceholders,
        minutesHint: "Quantos minutos antes do horário a confirmação será enviada.",
        note: 'Enviada com os botões "Confirmar" e "Cancelar" para o cliente responder.',
    },
};

const minutePresets = [
    { label: "1 hora", value: 60 },
    { label: "2 horas", value: 120 },
    { label: "3 horas", value: 180 },
    { label: "1 dia", value: 1440 },
];

const current = computed(() => types[props.type]);
const hasMinutes = computed(() => props.settings.minutes_before !== null);
const tabs = computed(() =>
    Object.entries(types).map(([key, meta]) => ({ key, label: meta.label })),
);

const form = useForm({
    enabled: props.settings.enabled,
    template: props.settings.template ?? "",
    ...(props.settings.minutes_before !== null
        ? { minutes_before: props.settings.minutes_before }
        : {}),
});

const textarea = ref(null);

function insertPlaceholder(placeholder) {
    const el = textarea.value;
    const value = form.template ?? "";

    if (!el) {
        form.template = value + placeholder;
        return;
    }

    const start = el.selectionStart ?? value.length;
    const end = el.selectionEnd ?? start;
    form.template = value.slice(0, start) + placeholder + value.slice(end);

    nextTick(() => {
        el.focus();
        const pos = start + placeholder.length;
        el.setSelectionRange(pos, pos);
    });
}

function submit() {
    form.put(route("settings.messages.update", { type: props.type }), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="`Mensagem de ${current.label}`" />

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
                        {{ form.processing ? "Salvando..." : "Salvar Mensagem" }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="route('settings.messages.show', { type: tab.key })"
                    class="pill px-4 py-2 text-sm font-medium"
                    :class="{ 'pill-active': tab.key === type }"
                >
                    {{ tab.label }}
                </Link>
            </div>

            <div class="card">
                <div class="card-body space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm text-secondary">
                            {{ current.description }}
                        </p>
                        <CheckboxInput v-model="form.enabled" label="Ativar" />
                    </div>

                    <div v-if="hasMinutes" class="space-y-2">
                        <FormField
                            label="Antecedência (Minutos)"
                            :error="form.errors.minutes_before"
                            :hint="current.minutesHint"
                        >
                            <TextInput
                                v-model="form.minutes_before"
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
                                        Number(form.minutes_before) ===
                                        preset.value,
                                }"
                                @click="form.minutes_before = preset.value"
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                    </div>

                    <FormField label="Mensagem" :error="form.errors.template">
                        <textarea
                            ref="textarea"
                            v-model="form.template"
                            class="form-control form-control-textarea"
                            rows="4"
                        />
                    </FormField>

                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="placeholder in current.placeholders"
                            :key="placeholder"
                            type="button"
                            class="badge badge-muted transition-colors hover:bg-surface-muted"
                            @click="insertPlaceholder(placeholder)"
                        >
                            {{ placeholder }}
                        </button>
                    </div>

                    <p v-if="current.note" class="text-xs text-secondary">
                        {{ current.note }}
                    </p>

                    <ul class="space-y-1 text-xs text-secondary">
                        <li
                            v-for="placeholder in current.placeholders"
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
        </form>
    </AppLayout>
</template>
