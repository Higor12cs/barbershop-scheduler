<script setup>
import { useHttp } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import Modal from '../Modal.vue';
import FormField from '../FormField.vue';
import TextInput from '../TextInput.vue';
import PhoneInput from '../PhoneInput.vue';
import Icon from '../Icon.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    customer: { type: Object, default: null },
    initialName: { type: String, default: '' },
});

const emit = defineEmits(['close', 'saved']);

const http = useHttp({
    name: '',
    phone: '',
    email: '',
    birth_date: '',
    active: true,
});

const errors = ref({});

watch(
    () => props.show,
    (visible) => {
        if (!visible) {
            return;
        }

        errors.value = {};
        http.name = props.customer?.name ?? props.initialName ?? '';
        http.phone = props.customer?.phone ?? '';
        http.email = props.customer?.email ?? '';
        http.birth_date = props.customer?.birth_date ?? '';
        http.active = true;
    },
);

function onSuccess(response) {
    const data = response?.data ?? response;

    emit('saved', data?.customer ?? data);
    emit('close');
}

function submit() {
    errors.value = {};

    const options = {
        onSuccess,
        onError: (received) => {
            errors.value = received || {};
        },
    };

    if (props.customer) {
        http.put(route('customers.quick-update', props.customer.id), options);
    } else {
        http.post(route('customers.quick-store'), options);
    }
}
</script>

<template>
    <Modal :show="show" :title="customer ? 'Editar Cliente' : 'Novo Cliente'" max-width="md" @close="emit('close')">
        <form class="space-y-4" @submit.prevent="submit">
            <FormField label="Nome" :error="errors.name">
                <TextInput v-model="http.name" autofocus />
            </FormField>

            <FormField label="Telefone" :error="errors.phone">
                <PhoneInput v-model="http.phone" />
            </FormField>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="E-mail" :error="errors.email">
                    <TextInput v-model="http.email" type="email" />
                </FormField>

                <FormField label="Data de Nascimento" :error="errors.birth_date">
                    <TextInput v-model="http.birth_date" type="date" />
                </FormField>
            </div>
        </form>

        <template #footer>
            <button type="button" class="btn btn-secondary" :disabled="http.processing" @click="emit('close')">Cancelar</button>
            <button type="button" class="btn btn-primary" :disabled="http.processing" @click="submit">
                <Icon name="check" class="size-4" />
                {{ http.processing ? 'Salvando...' : 'Salvar Cliente' }}
            </button>
        </template>
    </Modal>
</template>
