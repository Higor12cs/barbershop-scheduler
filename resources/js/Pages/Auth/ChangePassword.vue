<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';
import FormField from '../../Components/FormField.vue';
import TextInput from '../../Components/TextInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const page = usePage();
const mustChange = computed(() => page.props.auth.user?.must_change_password);

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('password.update'), {
        onFinish: () => form.reset(),
    });
}

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <GuestLayout
        title="Alterar Senha"
        :subtitle="mustChange ? 'Defina uma nova senha para continuar' : 'Escolha uma nova senha de acesso'"
    >
        <Head title="Alterar Senha" />

        <div v-if="mustChange" class="alert alert-warning mb-4">
            É necessário alterar a sua senha antes de continuar.
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <FormField label="Nova Senha" :error="form.errors.password" hint="Mínimo de 8 caracteres.">
                <TextInput v-model="form.password" type="password" autocomplete="new-password" autofocus />
            </FormField>

            <FormField label="Confirmar Nova Senha" :error="form.errors.password_confirmation">
                <TextInput v-model="form.password_confirmation" type="password" autocomplete="new-password" />
            </FormField>

            <button type="submit" class="btn btn-primary btn-block" :disabled="form.processing">
                {{ form.processing ? 'Salvando...' : 'Salvar Nova Senha' }}
            </button>
        </form>

        <button type="button" class="btn btn-secondary btn-block mt-3" @click="logout">
            Sair
        </button>
    </GuestLayout>
</template>
