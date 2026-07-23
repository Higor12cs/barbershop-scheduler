<script setup>
import { useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";
import Modal from "../Modal.vue";
import FormField from "../FormField.vue";
import SelectInput from "../SelectInput.vue";
import MoneyInput from "../MoneyInput.vue";
import Icon from "../Icon.vue";
import ConfirmDialog from "../ConfirmDialog.vue";
import CustomerCombobox from "./CustomerCombobox.vue";
import CustomerQuickForm from "./CustomerQuickForm.vue";
import { formatBRL } from "../../Support/money.js";

const props = defineProps({
    show: { type: Boolean, default: false },
    mode: { type: String, default: "create" },
    initial: { type: Object, default: null },
    appointmentId: { type: [Number, null], default: null },
    customers: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

const emit = defineEmits(["close"]);

const form = useForm({
    customer_id: null,
    employee_id: null,
    product_id: null,
    date: "",
    start_time: "",
    duration_minutes: "",
    price: "",
    notes: "",
});

const localCustomers = ref([]);
const showQuickForm = ref(false);
const quickCustomer = ref(null);
const quickInitialName = ref("");
const unavailableReason = ref(null);

watch(
    () => props.show,
    (visible) => {
        if (visible) {
            localCustomers.value = [...props.customers];
            unavailableReason.value = null;

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
    quickInitialName.value = name ?? "";
    showQuickForm.value = true;
}

function openEditCustomer(customer) {
    quickCustomer.value = customer;
    quickInitialName.value = "";
    showQuickForm.value = true;
}

function onCustomerSaved(customer) {
    const index = localCustomers.value.findIndex(
        (item) => item.id === customer.id,
    );

    if (index === -1) {
        localCustomers.value = [customer, ...localCustomers.value];
    } else {
        localCustomers.value.splice(index, 1, customer);
        localCustomers.value = [...localCustomers.value];
    }

    form.customer_id = customer.id;
    showQuickForm.value = false;
}

const employeeOptions = computed(() =>
    props.employees.map((employee) => ({
        value: employee.id,
        label: employee.name,
    })),
);

const serviceOptions = computed(() =>
    props.services.map((service) => ({
        value: service.id,
        label: `${service.name} · ${service.duration_minutes} min · ${formatBRL(service.price)}`,
    })),
);

const selectedService = computed(
    () =>
        props.services.find(
            (service) => service.id === Number(form.product_id),
        ) || null,
);

function onServiceChange(value) {
    form.product_id = value ? Number(value) : null;

    if (selectedService.value) {
        form.price = selectedService.value.price;
        form.duration_minutes = selectedService.value.duration_minutes;
    }
}

const title = computed(() =>
    props.mode === "edit" ? "Editar Agendamento" : "Criar Novo Agendamento",
);

function send(force) {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            unavailableReason.value = null;
            emit("close");
        },
        onError: (errors) => {
            unavailableReason.value = errors.availability ?? null;
        },
    };

    form.transform((data) => ({
        ...data,
        employee_id: Number(data.employee_id),
        force,
    }));

    if (props.mode === "edit") {
        form.patch(route("appointments.update", props.appointmentId), options);
    } else {
        form.post(route("appointments.store"), options);
    }
}

function submit() {
    send(false);
}

function forceSubmit() {
    unavailableReason.value = null;
    send(true);
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
                <SelectInput
                    :model-value="form.product_id"
                    :options="serviceOptions"
                    placeholder="Selecione"
                    @update:model-value="onServiceChange"
                />
            </FormField>

            <FormField label="Funcionário" :error="form.errors.employee_id">
                <SelectInput
                    v-model="form.employee_id"
                    :options="employeeOptions"
                    placeholder="Selecione"
                />
            </FormField>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Data" :error="form.errors.date">
                    <input
                        v-model="form.date"
                        type="date"
                        class="form-control"
                    />
                </FormField>

                <FormField label="Horário" :error="form.errors.start_time">
                    <input
                        v-model="form.start_time"
                        type="time"
                        step="900"
                        class="form-control"
                    />
                </FormField>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField
                    label="Duração (Minutos)"
                    :error="form.errors.duration_minutes"
                >
                    <input
                        v-model="form.duration_minutes"
                        type="number"
                        step="5"
                        min="1"
                        class="form-control"
                        placeholder="30"
                    />
                </FormField>

                <FormField label="Preço (R$)" :error="form.errors.price">
                    <MoneyInput v-model="form.price" />
                </FormField>
            </div>

            <FormField label="Observações" :error="form.errors.notes">
                <textarea
                    v-model="form.notes"
                    class="form-control form-control-textarea"
                    placeholder="Anotações..."
                />
            </FormField>
        </form>

        <CustomerQuickForm
            :show="showQuickForm"
            :customer="quickCustomer"
            :initial-name="quickInitialName"
            @saved="onCustomerSaved"
            @close="showQuickForm = false"
        />

        <ConfirmDialog
            :show="unavailableReason !== null"
            title="Horário Indisponível"
            :message="`${unavailableReason} Deseja agendar mesmo assim?`"
            confirm-label="Agendar Mesmo Assim"
            variant="primary"
            :processing="form.processing"
            @confirm="forceSubmit"
            @cancel="unavailableReason = null"
        />

        <template #footer>
            <button
                type="button"
                class="btn btn-secondary"
                :disabled="form.processing"
                @click="emit('close')"
            >
                Cancelar
            </button>
            <button
                type="button"
                class="btn btn-primary"
                :disabled="form.processing"
                @click="submit"
            >
                <Icon name="check" class="size-4" />
                {{ form.processing ? "Salvando..." : "Salvar Agendamento" }}
            </button>
        </template>
    </Modal>
</template>
