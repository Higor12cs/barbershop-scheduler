<script setup>
import Icon from '../../Components/Icon.vue';
import FormField from '../../Components/FormField.vue';
import TextInput from '../../Components/TextInput.vue';
import PhoneInput from '../../Components/PhoneInput.vue';
import CheckboxInput from '../../Components/CheckboxInput.vue';

defineProps({
    form: { type: Object, required: true },
    colors: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Dados do Funcionário</h2>
        </div>
        <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Nome" :error="form.errors.name">
                <TextInput v-model="form.name" autofocus />
            </FormField>

            <FormField label="Telefone" :error="form.errors.phone">
                <PhoneInput v-model="form.phone" />
            </FormField>

            <FormField label="E-mail" :error="form.errors.email">
                <TextInput v-model="form.email" type="email" />
            </FormField>

            <FormField label="Cor na Agenda" :error="form.errors.color" hint="Identifica o funcionário nos agendamentos.">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="color in colors"
                        :key="color.value"
                        type="button"
                        class="flex size-9 items-center justify-center rounded-full transition-transform hover:scale-110"
                        :class="color.class"
                        :title="color.label"
                        @click="form.color = color.value"
                    >
                        <Icon v-if="form.color === color.value" name="check" class="size-4 text-white" />
                    </button>
                </div>
            </FormField>

            <FormField label="Status">
                <CheckboxInput v-model="form.active" label="Funcionário ativo" />
            </FormField>
        </div>
    </div>
</template>
