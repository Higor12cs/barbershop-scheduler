<script setup>
import { ref } from 'vue';
import FormField from '../../../Components/FormField.vue';
import TextInput from '../../../Components/TextInput.vue';
import SelectInput from '../../../Components/SelectInput.vue';
import CheckboxInput from '../../../Components/CheckboxInput.vue';

defineProps({
    form: { type: Object, required: true },
    moduleOptions: { type: Array, default: () => [] },
    providerOptions: { type: Array, default: () => [] },
    webhookUrl: { type: String, default: null },
    showAdminFields: { type: Boolean, default: false },
});

const copied = ref(false);

function copyWebhook(url) {
    if (!url) {
        return;
    }

    navigator.clipboard?.writeText(url).then(() => {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    });
}
</script>

<template>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Dados do Ambiente</h2>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Nome" :error="form.errors.name">
                    <TextInput v-model="form.name" autofocus />
                </FormField>

                <FormField label="Slug" :error="form.errors.slug" hint="Identificador único, apenas letras, números e hífens.">
                    <TextInput v-model="form.slug" />
                </FormField>

                <FormField label="Acesso até" :error="form.errors.access_until" hint="Deixe em branco para acesso sem data de expiração.">
                    <TextInput v-model="form.access_until" type="date" />
                </FormField>

                <FormField label="Status">
                    <CheckboxInput v-model="form.active" label="Ambiente ativo" />
                </FormField>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Módulos</h2>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <CheckboxInput
                        v-for="option in moduleOptions"
                        :key="option.value"
                        v-model="form.modules"
                        :value="option.value"
                        :label="option.label"
                    />
                </div>
                <p v-if="form.errors.modules" class="form-error mt-2">{{ form.errors.modules }}</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">WhatsApp</h2>
            </div>
            <div class="card-body space-y-4">
                <FormField label="Provedor" :error="form.errors.whatsapp_provider">
                    <SelectInput
                        v-model="form.whatsapp_provider"
                        :options="providerOptions"
                        placeholder="Nenhum"
                    />
                </FormField>

                <div v-if="form.whatsapp_provider === 'dichat'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField label="URL base" :error="form.errors['whatsapp_config.base_url']" hint="Servidor da instância dichat, ex.: api11, api12.">
                        <TextInput v-model="form.whatsapp_config.base_url" placeholder="https://api11.dichat.com.br" />
                    </FormField>

                    <FormField label="Token" :error="form.errors['whatsapp_config.token']" hint="Token da conexão (Bearer) do painel dichat.">
                        <TextInput v-model="form.whatsapp_config.token" />
                    </FormField>
                </div>

                <FormField
                    v-if="webhookUrl && form.whatsapp_provider"
                    label="URL do webhook"
                    hint="Configure esta URL no painel do provedor para receber as respostas dos clientes."
                >
                    <div class="flex items-center gap-2">
                        <input :value="webhookUrl" readonly class="form-control" @focus="$event.target.select()">
                        <button type="button" class="btn btn-secondary shrink-0" @click="copyWebhook(webhookUrl)">
                            {{ copied ? 'Copiado!' : 'Copiar' }}
                        </button>
                    </div>
                </FormField>
            </div>
        </div>

        <div v-if="showAdminFields" class="card">
            <div class="card-header">
                <h2 class="card-title">Usuário Administrador</h2>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Nome" :error="form.errors.admin_name">
                    <TextInput v-model="form.admin_name" />
                </FormField>

                <FormField label="E-mail" :error="form.errors.admin_email">
                    <TextInput v-model="form.admin_email" type="email" />
                </FormField>

                <FormField label="Senha" :error="form.errors.admin_password" hint="O administrador deverá alterá-la no primeiro acesso.">
                    <TextInput v-model="form.admin_password" type="password" />
                </FormField>
            </div>
        </div>
    </div>
</template>
