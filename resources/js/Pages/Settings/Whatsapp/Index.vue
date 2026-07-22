<script setup>
import { Head, router, useForm, usePage, usePoll } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import FormField from "../../../Components/FormField.vue";
import TextInput from "../../../Components/TextInput.vue";
import PhoneInput from "../../../Components/PhoneInput.vue";
import EmptyState from "../../../Components/EmptyState.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

const QR_REFRESH_SECONDS = 20;

const props = defineProps({
    configured: { type: Boolean, default: false },
    connection: { type: Object, default: null },
});

const page = usePage();

const connected = computed(() => props.connection?.connected === true);

const qrSrc = computed(() => {
    const qr = props.connection?.qr;

    if (!qr) {
        return null;
    }

    return qr.startsWith("data:") || qr.startsWith("http")
        ? qr
        : `data:image/png;base64,${qr}`;
});

const countdown = ref(QR_REFRESH_SECONDS);
let ticker = null;

const { start: startPolling, stop: stopPolling } = usePoll(
    QR_REFRESH_SECONDS * 1000,
    {
        only: ["connection"],
        onStart: () => (countdown.value = QR_REFRESH_SECONDS),
    },
    { autoStart: false },
);

function startCountdown() {
    stopCountdown();
    countdown.value = QR_REFRESH_SECONDS;
    ticker = window.setInterval(() => {
        countdown.value =
            countdown.value > 1 ? countdown.value - 1 : QR_REFRESH_SECONDS;
    }, 1000);
}

function stopCountdown() {
    if (ticker) {
        window.clearInterval(ticker);
        ticker = null;
    }
}

watch(
    connected,
    (isConnected) => {
        if (props.configured && !isConnected) {
            startPolling();
            startCountdown();
        } else {
            stopPolling();
            stopCountdown();
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    stopPolling();
    stopCountdown();
});

const testForm = useForm({
    phone: "",
    message: `Mensagem de teste do ${page.props.appName}.`,
});

function restart() {
    router.post(
        route("settings.whatsapp.restart"),
        {},
        { preserveScroll: true },
    );
}

function sendTest() {
    testForm.post(route("settings.whatsapp.test"), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Conexão do WhatsApp" />

        <div class="space-y-6">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Conexão do WhatsApp</h1>
                    <p class="page-header-subtitle">
                        Gerencie a conexão do WhatsApp da sua barbearia.
                    </p>
                </div>
            </div>

            <div v-if="!configured" class="card">
                <EmptyState
                    icon="message-circle"
                    title="WhatsApp Não Configurado"
                    description="Nenhum provedor de WhatsApp foi configurado para este ambiente. Entre em contato com o administrador."
                />
            </div>

            <template v-else>
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Status da Conexão</h2>
                    </div>
                    <div class="card-body space-y-4">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="badge"
                                    :class="
                                        connected
                                            ? 'badge-success'
                                            : 'badge-danger'
                                    "
                                >
                                    {{
                                        connected ? "Conectado" : "Desconectado"
                                    }}
                                </span>
                                <span
                                    v-if="connected && connection?.phone"
                                    class="text-sm text-secondary"
                                >
                                    {{ connection.phone }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="btn btn-secondary"
                                    @click="restart"
                                >
                                    <Icon name="rotate-ccw" class="size-4" />
                                    Reiniciar Conexão
                                </button>
                            </div>
                        </div>

                        <div v-if="connected" class="alert alert-info">
                            Para desconectar, abra o WhatsApp no celular, acesse
                            <span class="font-semibold"
                                >Aparelhos Conectados</span
                            >
                            e remova esta sessão.
                        </div>

                        <div
                            v-if="!connected"
                            class="flex flex-col items-center gap-4 rounded-xl border border-border bg-surface-alt p-6"
                        >
                            <img
                                v-if="qrSrc"
                                :src="qrSrc"
                                alt="QR Code do WhatsApp"
                                class="size-56 rounded-lg border border-border bg-surface"
                            />
                            <p v-else class="text-sm text-secondary">
                                QR Code indisponível no momento.
                            </p>
                            <p class="text-sm text-secondary">
                                {{
                                    qrSrc
                                        ? "Escaneie com o WhatsApp do seu celular."
                                        : "Buscando um novo código..."
                                }}
                                Atualiza automaticamente em {{ countdown }}s.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Enviar Mensagem de Teste</h2>
                    </div>
                    <form
                        class="card-body space-y-4"
                        @submit.prevent="sendTest"
                    >
                        <FormField
                            label="Telefone"
                            :error="testForm.errors.phone"
                            hint="Com DDD."
                        >
                            <PhoneInput v-model="testForm.phone" />
                        </FormField>

                        <FormField
                            label="Mensagem"
                            :error="testForm.errors.message"
                        >
                            <textarea
                                v-model="testForm.message"
                                class="form-control form-control-textarea"
                                rows="3"
                            />
                        </FormField>

                        <div>
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="testForm.processing"
                            >
                                <Icon name="message-circle" class="size-4" />
                                {{
                                    testForm.processing
                                        ? "Enviando..."
                                        : "Enviar Mensagem de Teste"
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </AppLayout>
</template>
