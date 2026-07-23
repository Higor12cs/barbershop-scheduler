<script setup>
import { computed, watch } from 'vue';
import FormField from '../../Components/FormField.vue';
import SelectInput from '../../Components/SelectInput.vue';
import TextInput from '../../Components/TextInput.vue';
import CheckboxInput from '../../Components/CheckboxInput.vue';

const props = defineProps({
    form: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
});

const employeeOptions = computed(() => [{ value: '', label: 'Todos os funcionários' }, ...props.employees]);

watch(
    () => props.form.start_date,
    (value) => {
        if (value && (!props.form.end_date || props.form.end_date < value)) {
            props.form.end_date = value;
        }
    },
);
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dados do Bloqueio</h2>
        </div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
                label="Funcionário"
                :error="form.errors.employee_id"
                hint="Deixe em “Todos” para fechar a agenda inteira, como em um feriado."
                class="sm:col-span-2"
            >
                <SelectInput v-model="form.employee_id" :options="employeeOptions" />
            </FormField>

            <FormField label="Período" class="sm:col-span-2">
                <CheckboxInput v-model="form.all_day" label="Bloquear os dias inteiros" />
            </FormField>

            <FormField label="Data de Início" :error="form.errors.start_date">
                <input v-model="form.start_date" type="date" class="form-control">
            </FormField>

            <FormField label="Data de Fim" :error="form.errors.end_date">
                <input v-model="form.end_date" type="date" class="form-control">
            </FormField>

            <template v-if="!form.all_day">
                <FormField label="Horário de Início" :error="form.errors.start_time">
                    <input v-model="form.start_time" type="time" step="300" class="form-control">
                </FormField>

                <FormField label="Horário de Fim" :error="form.errors.end_time">
                    <input v-model="form.end_time" type="time" step="300" class="form-control">
                </FormField>
            </template>

            <FormField
                label="Motivo"
                :error="form.errors.reason"
                hint="Aparece na agenda sobre a faixa bloqueada."
                class="sm:col-span-2"
            >
                <TextInput v-model="form.reason" placeholder="Férias, folga, feriado..." />
            </FormField>
        </div>
    </div>
</template>
