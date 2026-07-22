<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';
import Modal from '../Modal.vue';
import FormField from '../FormField.vue';
import SelectInput from '../SelectInput.vue';
import MoneyInput from '../MoneyInput.vue';
import Icon from '../Icon.vue';
import CustomerCombobox from './CustomerCombobox.vue';
import CustomerQuickForm from './CustomerQuickForm.vue';
import { formatBRL } from '../../Support/money.js';

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: 'create' },
    initial: { type: Object, default: null },
    appointmentId: { type: [Number, null], default: null },
    customers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const form = useForm({
    customer_id: null,
    employee_id: null,
    product_id: null,
    date: '',
    start_time: '',
    price: '',
    notes: '',
});

const localCustomers = ref([]);
const showQuickForm = ref(false);
const quickCustomer = ref(null);
const quickInitialName = ref('');

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            localCustomers.value = [...props.customers];

            if (props.initial) {
                form.clearErrors();
                form.defaults({ ...props.initial });
                form.reset();
            }
        }
    },
);

function openCreateCustomer(name) {
    quickCustomer.value = null;
    quickInitialName.value = name ?? '';
    showQuickForm.value = true;
}

function openEditCustomer(customer) {
    quickCustomer.value = customer;
    quickInitialName.value = '';
    showQuickForm.value = true;
}

function onCustomerSaved(customer) {
    const index = localCustomers.value.findIndex((item) => item.id === customer.id);

    if (index === -1) {
        localCustomers.value = [customer, ...localCustomers.value];
    } else {
        localCustomers.value.splice(index, 1, customer);
        localCustomers.value = [...localCustomers.value];
    }

    form.customer_id = customer.id;
    showQuickForm.value = false;
}

const employeeOptions = computed(() => props.employees.map((employee) => ({ value: employee.id, label: employee.name })));

const serviceOptions = computed(() =>
    props.services.map((service) => ({
        value: service.id,
        label: `${service.name} · ${service.duration_minutes} min · ${formatBRL(service.price)}`,
    })),
);

const selectedService = computed(() => props.services.find((service) => service.id === Number(form.product_id)) || null);

function onServiceChange(value) {
    form.product_id = value ? Number(value) : null;

    if (selectedService.value) {
        form.price = selectedService.value.price;
    }
}

const title = computed(() => (props.mode === 'edit' ? 'Editar Agendamento' : 'Criar Novo Agendamento'));

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    };

    if (props.mode === 'edit') {
        form.transform((data) => ({ ...data, employee_id: Number(data.employee_id) })).patch(route('appointments.update', props.appointmentId), options);
    } else {
        form.transform((data) => ({ ...data, employee_id: Number(data.employee_id) })).post(route('appointments.store'), options);
    }
}
</script>

<template>
    <Modal :show="show" :title="title" max-width="lg" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField label="Cliente" :error="form.errors.customer_id">
                <CustomerCombobox
                    v-model="form.customer_id"
                    :customers="localCustomers"
                    @create="openCreateCustomer"
                    @edit="openEditCustomer"
                />
            </FormField>

            <FormField label="Serviço" :error="form.errors.product_id">
                <SelectInput :model-value="form.product_id" :options="serviceOptions" placeholder="Selecione o serviço" @update:model-value="onServiceChange" />
            </FormField>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Funcionário" :error="form.errors.employee_id">
                    <SelectInput v-model="form.employee_id" :options="employeeOptions" placeholder="Selecione o funcionário" />
                </FormField>

                <FormField label="Preço (R$)" :error="form.errors.price">
                    <MoneyInput v-model="form.price" />
                </FormField>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Data" :error="form.errors.date">
                    <input v-model="form.date" type="date" class="form-control">
                </FormField>

                <FormField label="Horário" :error="form.errors.start_time">
                    <input v-model="form.start_time" type="time" step="900" class="form-control">
                </FormField>
            </div>

            <FormField label="Observações" :error="form.errors.notes">
                <textarea v-model="form.notes" class="form-control form-control-textarea" placeholder="Anotações do atendimento..." />
            </FormField>
        </form>

        <CustomerQuickForm
            :show="showQuickForm"
            :customer="quickCustomer"
            :initial-name="quickInitialName"
            @saved="onCustomerSaved"
            @close="showQuickForm = false"
        />

        <template #footer>
            <button type="button" class="btn btn-secondary" :disabled="form.processing" @click="emit('close')">Cancelar</button>
            <button type="button" class="btn btn-primary" :disabled="form.processing" @click="submit">
                <Icon name="check" class="size-4" />
                {{ form.processing ? 'Salvando...' : 'Salvar Agendamento' }}
            </button>
        </template>
    </Modal>
</template>
