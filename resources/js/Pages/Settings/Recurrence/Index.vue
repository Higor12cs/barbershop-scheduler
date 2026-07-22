<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import Icon from "../../../Components/Icon.vue";
import FormField from "../../../Components/FormField.vue";
import TextInput from "../../../Components/TextInput.vue";
import AppLayout from "../../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    recurrence_horizon_days: props.settings.recurrence_horizon_days,
});

function submit() {
    form.put(route("settings.recurrence.update"), { preserveScroll: true });
}
</script>

<template>
    <AppLayout>
        <Head title="Recorrências" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Recorrências</h1>
                    <p class="page-header-subtitle">
                        Configure como os agendamentos recorrentes são gerados.
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
