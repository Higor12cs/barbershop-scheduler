<script setup>
import FormField from '../../Components/FormField.vue';
import TextInput from '../../Components/TextInput.vue';
import PhoneInput from '../../Components/PhoneInput.vue';
import CheckboxInput from '../../Components/CheckboxInput.vue';

defineProps({
    form: { type: Object, required: true },
});
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dados do Cliente</h2>
        </div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Nome" :error="form.errors.name">
                <TextInput v-model="form.name" autofocus />
            </FormField>

            <FormField label="Telefone" :error="form.errors.phone" hint="Será usado para contato via WhatsApp.">
                <PhoneInput v-model="form.phone" />
            </FormField>

            <FormField label="E-mail" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" />
            </FormField>

            <FormField label="Data de Nascimento" :error="form.errors.birth_date">
                <TextInput v-model="form.birth_date" type="date" />
            </FormField>

            <FormField label="Observações" :error="form.errors.notes" class="sm:col-span-2">
                <textarea v-model="form.notes" class="form-control form-control-textarea" />
            </FormField>

            <FormField label="Status">
                <CheckboxInput v-model="form.active" label="Cliente ativo" />
            </FormField>
        </div>
    </div>
</template>
