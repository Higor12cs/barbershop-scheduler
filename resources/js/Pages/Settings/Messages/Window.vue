<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import FormField from "../../../Components/FormField.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";
import { minutesToTime, timeToMinutes } from "../../../Support/date.js";

const props = defineProps({
    settings: { type: Object, required: true },
});

const tabs = [
    { key: "booking", label: "Agendamento" },
    { key: "reminder", label: "Lembrete" },
    { key: "confirmation", label: "Confirmação" },
];

const form = useForm({
    send_window_start: props.settings.send_window_start,
    send_window_end: props.settings.send_window_end,
});

const startMinutes = computed(() =>
    timeToMinutes(form.send_window_start || "08:00"),
);
const endMinutes = computed(() =>
    timeToMinutes(form.send_window_end || "20:00"),
);

function durationLabel(minutes) {
    if (minutes % 1440 === 0) {
        const days = minutes / 1440;

        return days === 1 ? "1 dia" : `${days} dias`;
    }

    if (minutes % 60 === 0) {
        const hours = minutes / 60;

        return hours === 1 ? "1 hora" : `${hours} horas`;
    }

    return `${minutes} minutos`;
}

/**
 * Below this time of day the send would fall before the window opens, so it
 * moves to the previous evening.
 */
function cutoff(lead) {
    return minutesToTime(Math.min(startMinutes.value + lead, 1440));
}

const messages = computed(() =>
    [
        {
            label: "Lembrete",
            enabled: props.settings.reminder_enabled,
            minutes: props.settings.reminder_minutes_before,
        },
        {
            label: "Confirmação",
            enabled: props.settings.confirmation_enabled,
            minutes: props.settings.confirmation_minutes_before,
        },
    ].filter((item) => item.enabled),
);

function submit() {
    form.put(route("settings.messages.window.update"), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Janela de Envio" />

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
                        {{ form.processing ? "Salvando..." : "Salvar Janela" }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="route('settings.messages.show', { type: tab.key })"
                    class="pill px-4 py-2 text-sm font-medium"
                >
                    {{ tab.label }}
                </Link>
                <Link
                    :href="route('settings.messages.window')"
                    class="pill pill-active px-4 py-2 text-sm font-medium"
                >
                    Janela de Envio
                </Link>
            </div>

            <div class="alert alert-info space-y-3">
                <p class="flex items-center gap-2 font-semibold">
                    <Icon name="info" class="size-4 shrink-0" />
                    Como funciona
                </p>

                <p>
                    <span class="font-medium"
                        >A antecedência fica em cada mensagem.</span
                    >
                    Nas abas <em>Lembrete</em> e <em>Confirmação</em> você
                    define quantos minutos antes do atendimento a mensagem deve
                    sair. Esta janela não muda essa antecedência — ela só define
                    o intervalo do dia em que é aceitável falar com o cliente.
                </p>

                <p>
                    <span class="font-medium"
                        >Fora da janela, o envio é antecipado.</span
                    >
                    Se o disparo cairia antes de {{ form.send_window_start }},
                    ele sai às {{ form.send_window_end }}
                    <span class="font-medium">do dia anterior</span>. Se cairia
                    depois de {{ form.send_window_end }}, sai às
                    {{ form.send_window_end }} do mesmo dia. Nunca é adiado —
                    uma mensagem que chega depois do atendimento não serve para
                    nada.
                </p>

                <p>
                    <span class="font-medium">Consequência prática.</span>
                    Atendimentos logo no começo do dia são avisados na véspera.
                    Com a janela atual, um lembrete de 2 horas faz todo
                    atendimento antes das {{ cutoff(120) }} ser avisado às
                    {{ form.send_window_end }} da noite anterior; de
                    {{ cutoff(120) }} em diante o aviso sai no próprio dia, com
                    as 2 horas cheias.
                </p>

                <p>
                    <span class="font-medium">Nada se perde.</span>
                    Se o sistema ficar fora do ar, as mensagens pendentes saem
                    na próxima abertura da janela — desde que o atendimento
                    ainda não tenha começado. Cada mensagem é enviada uma única
                    vez, mesmo que você mude a antecedência depois que ela já
                    saiu.
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Horário Permitido para Envio</h2>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <FormField
                            label="Início"
                            :error="form.errors.send_window_start"
                        >
                            <input
                                v-model="form.send_window_start"
                                type="time"
                                step="300"
                                class="form-control"
                            />
                        </FormField>

                        <FormField
                            label="Fim"
                            :error="form.errors.send_window_end"
                        >
                            <input
                                v-model="form.send_window_end"
                                type="time"
                                step="300"
                                class="form-control"
                            />
                        </FormField>
                    </div>

                    <div v-if="messages.length" class="space-y-2">
                        <p class="text-sm font-medium">
                            Mensagens sujeitas a esta janela:
                        </p>
                        <div
                            v-for="item in messages"
                            :key="item.label"
                            class="rounded-lg border border-border bg-surface-muted p-3 text-sm text-secondary"
                        >
                            <span class="font-medium text-foreground">{{
                                item.label
                            }}</span>
                            — {{ durationLabel(item.minutes) }} antes do
                            atendimento.
                            <template v-if="startMinutes + item.minutes < 1440">
                                Atendimentos antes das
                                {{ cutoff(item.minutes) }} são avisados às
                                {{ form.send_window_end }} da véspera.
                            </template>
                            <template v-else>
                                Com essa antecedência, o aviso sempre cai na
                                véspera ou antes.
                            </template>
                        </div>
                    </div>

                    <p v-else class="text-sm text-secondary">
                        Nenhuma mensagem automática está ativa. A janela passa a
                        valer assim que você ativar o lembrete ou a confirmação.
                    </p>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
