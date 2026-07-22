<script setup>
import { ref, watch } from 'vue';
import { route } from 'ziggy-js';
import FormField from '../../../Components/FormField.vue';
import TextInput from '../../../Components/TextInput.vue';
import SelectInput from '../../../Components/SelectInput.vue';

const props = defineProps({
    form: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    editing: { type: Boolean, default: false },
    nameLocked: { type: Boolean, default: false },
});

const emailExists = ref(false);

let checkTimeout = null;

watch(
    () => props.form.email,
    (value) => {
        if (props.editing) {
            return;
        }

        clearTimeout(checkTimeout);

        if (!value || !value.includes('@')) {
            emailExists.value = false;
            return;
        }

        checkTimeout = setTimeout(async () => {
            try {
                const response = await fetch(
                    `${route('settings.users.check-email')}?email=${encodeURIComponent(value)}`,
                    { headers: { Accept: 'application/json' }, credentials: 'same-origin' },
                );
                const data = await response.json();
                emailExists.value = Boolean(data.exists);
            } catch {
                emailExists.value = false;
            }
        }, 400);
    },
);
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dados do Usuário</h2>
        </div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField
                label="Nome"
                :error="form.errors.name"
                :hint="nameLocked ? 'Este usuário pertence a outros ambientes e o nome não pode ser alterado aqui.' : null"
            >
                <TextInput v-model="form.name" :disabled="nameLocked" :autofocus="!editing" />
            </FormField>

            <FormField label="E-mail" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" :disabled="editing" />
                <p v-if="emailExists" class="text-xs text-info">
                    Este e-mail já possui cadastro. O usuário será vinculado a este ambiente com o papel selecionado.
                </p>
            </FormField>

            <FormField
                v-if="!editing && !emailExists"
                label="Senha"
                :error="form.errors.password"
                hint="O usuário deverá alterá-la no primeiro acesso."
            >
                <TextInput v-model="form.password" type="password" />
            </FormField>

            <FormField label="Papel" :error="form.errors.role">
                <SelectInput v-model="form.role" :options="roles" placeholder="Selecione um papel" />
            </FormField>
        </div>
    </div>
</template>
