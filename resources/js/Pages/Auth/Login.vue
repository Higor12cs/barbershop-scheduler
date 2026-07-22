<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import FormField from '../../Components/FormField.vue';
import TextInput from '../../Components/TextInput.vue';
import CheckboxInput from '../../Components/CheckboxInput.vue';
import GuestLayout from '../../Layouts/GuestLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <GuestLayout title="Entrar" subtitle="Acesse o seu ambiente para continuar">
        <Head title="Entrar" />

        <form class="space-y-4" @submit.prevent="submit">
            <FormField label="E-mail" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" autocomplete="email" autofocus />
            </FormField>

            <FormField label="Senha" :error="form.errors.password">
                <TextInput v-model="form.password" type="password" autocomplete="current-password" />
            </FormField>

            <CheckboxInput v-model="form.remember" label="Manter conectado" />

            <button type="submit" class="btn btn-primary btn-block" :disabled="form.processing">
                {{ form.processing ? 'Entrando...' : 'Entrar' }}
            </button>
        </form>
    </GuestLayout>
</template>
