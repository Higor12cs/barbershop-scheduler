<script setup>
import { computed, ref, watch } from 'vue';
import FormField from '../../Components/FormField.vue';
import SelectInput from '../../Components/SelectInput.vue';
import CheckboxInput from '../../Components/CheckboxInput.vue';
import CustomerCombobox from '../../Components/Schedule/CustomerCombobox.vue';
import { formatBRL } from '../../Support/money.js';

const props = defineProps({
    form: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    timeSlots: { type: Array, default: () => [] },
});

const intervalShortcuts = [
    { value: 7, label: 'Semanal (7)' },
    { value: 14, label: 'Quinzenal (14)' },
    { value: 30, label: 'Mensal (30)' },
];

const serviceOptions = computed(() =>
    props.services.map((service) => ({
        value: service.id,
        label: `${service.name} · ${service.duration_minutes} min · ${formatBRL(service.price)}`,
    })),
);

const employeeOptions = computed(() => props.employees);

const timeOptions = computed(() => props.timeSlots.map((slot) => ({ value: slot, label: slot })));

const noEndDate = ref(!props.form.ends_on);

watch(noEndDate, (value) => {
    if (value) {
        props.form.ends_on = null;
    }
});
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dados da Recorrência</h2>
        </div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Cliente" :error="form.errors.customer_id" class="sm:col-span-2">
                <CustomerCombobox v-model="form.customer_id" :customers="customers" />
            </FormField>

            <FormField label="Serviço" :error="form.errors.product_id">
                <SelectInput
                    v-model="form.product_id"
                    :options="serviceOptions"
                    placeholder="Selecione um serviço"
                />
            </FormField>

            <FormField label="Funcionário" :error="form.errors.employee_id">
                <SelectInput
                    v-model="form.employee_id"
                    :options="employeeOptions"
                    placeholder="Selecione um funcionário"
                />
            </FormField>

            <FormField label="Horário" :error="form.errors.time">
                <SelectInput v-model="form.time" :options="timeOptions" placeholder="Selecione o horário" />
            </FormField>

            <FormField label="Intervalo" :error="form.errors.interval_days">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="shortcut in intervalShortcuts"
                            :key="shortcut.value"
                            type="button"
                            class="pill px-3 py-1.5 text-sm font-medium"
                            :class="{ 'pill-active': Number(form.interval_days) === shortcut.value }"
                            @click="form.interval_days = shortcut.value"
                        >
                            {{ shortcut.label }}
                        </button>
                    </div>
                    <input
                        v-model="form.interval_days"
                        type="number"
                        min="1"
                        max="365"
                        step="1"
                        class="form-control"
                        placeholder="A cada quantos dias"
                    >
                </div>
            </FormField>

            <FormField label="Data de Início" :error="form.errors.starts_on">
                <input v-model="form.starts_on" type="date" class="form-control">
            </FormField>

            <FormField label="Data Limite" :error="form.errors.ends_on">
                <input
                    v-model="form.ends_on"
                    type="date"
                    class="form-control"
                    :disabled="noEndDate"
                >
                <CheckboxInput v-model="noEndDate" label="Sem data limite" class="mt-2" />
            </FormField>

            <FormField label="Observações" :error="form.errors.notes" class="sm:col-span-2">
                <textarea v-model="form.notes" class="form-control form-control-textarea" />
            </FormField>

            <FormField label="Status">
                <CheckboxInput v-model="form.active" label="Recorrência ativa" />
            </FormField>
        </div>
    </div>
</template>
