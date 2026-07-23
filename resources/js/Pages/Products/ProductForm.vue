<script setup>
import FormField from "../../Components/FormField.vue";
import TextInput from "../../Components/TextInput.vue";
import MoneyInput from "../../Components/MoneyInput.vue";
import CheckboxInput from "../../Components/CheckboxInput.vue";

defineProps({
  form: { type: Object, required: true },
  types: { type: Array, default: () => [] },
});
</script>

<template>
  <div class="card">
    <div class="card-header">
      <h2 class="card-title">Dados do Produto</h2>
    </div>
    <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
      <FormField label="Tipo" :error="form.errors.type" class="sm:col-span-2">
        <div class="flex items-center gap-2">
          <button
            v-for="option in types"
            :key="option.value"
            type="button"
            class="pill px-4 py-2 text-sm font-medium"
            :class="{ 'pill-active': form.type === option.value }"
            @click="form.type = option.value"
          >
            {{ option.label }}
          </button>
        </div>
      </FormField>

      <FormField label="Nome" :error="form.errors.name" class="sm:col-span-2">
        <TextInput v-model="form.name" autofocus />
      </FormField>

      <FormField label="Preço (R$)" :error="form.errors.price">
        <MoneyInput v-model="form.price" />
      </FormField>

      <FormField label="Custo (R$)" :error="form.errors.cost">
        <MoneyInput v-model="form.cost" />
      </FormField>

      <FormField
        v-if="form.type === 'service'"
        label="Duração (Minutos)"
        :error="form.errors.duration_minutes"
      >
        <input
          v-model="form.duration_minutes"
          type="number"
          step="1"
          min="1"
          class="form-control"
          placeholder="30"
        />
      </FormField>

      <FormField
        label="Descrição"
        :error="form.errors.description"
        class="sm:col-span-2"
      >
        <textarea
          v-model="form.description"
          class="form-control form-control-textarea"
        />
      </FormField>

      <FormField label="Status">
        <CheckboxInput v-model="form.active" label="Produto Ativo" />
      </FormField>
    </div>
  </div>
</template>
