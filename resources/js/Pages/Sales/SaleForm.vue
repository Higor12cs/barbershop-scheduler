<script setup>
import { computed } from 'vue';
import Icon from '../../Components/Icon.vue';
import FormField from '../../Components/FormField.vue';
import SelectInput from '../../Components/SelectInput.vue';
import MoneyInput from '../../Components/MoneyInput.vue';
import CustomerCombobox from '../../Components/Schedule/CustomerCombobox.vue';
import { formatBRL } from '../../Support/money.js';

const props = defineProps({
    form: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const productOptions = computed(() =>
    props.products.map((product) => ({
        value: product.value,
        label: `${product.label} · ${product.type_label}`,
    })),
);

function onProductChange(item, value) {
    item.product_id = value ? Number(value) : '';

    const product = props.products.find((option) => option.value === Number(value));

    if (product) {
        item.unit_price = String(product.price);
    }
}

function addItem() {
    props.form.items.push({ product_id: '', quantity: 1, unit_price: '' });
}

function removeItem(index) {
    props.form.items.splice(index, 1);
}

function subtotal(item) {
    const quantity = Number(item.quantity) || 0;
    const unitPrice = Number(item.unit_price) || 0;

    return quantity * unitPrice;
}

const total = computed(() => props.form.items.reduce((sum, item) => sum + subtotal(item), 0));
</script>

<template>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Dados da Venda</h2>
            </div>
            <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                <FormField label="Cliente" :error="form.errors.customer_id">
                    <CustomerCombobox v-model="form.customer_id" :customers="customers" />
                </FormField>

                <FormField label="Funcionário" :error="form.errors.employee_id">
                    <SelectInput v-model="form.employee_id" :options="employees" placeholder="Sem funcionário" />
                </FormField>

                <FormField label="Data" :error="form.errors.date">
                    <input v-model="form.date" type="date" class="form-control">
                </FormField>

                <FormField label="Horário" :error="form.errors.time">
                    <input v-model="form.time" type="time" class="form-control">
                </FormField>
            </div>
        </div>

        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h2 class="card-title">Itens da Venda</h2>
                <button type="button" class="btn btn-secondary" @click="addItem">
                    <Icon name="plus" class="size-4" />
                    Adicionar Item
                </button>
            </div>
            <div class="card-body space-y-4">
                <p v-if="form.errors.items" class="form-error">{{ form.errors.items }}</p>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="grid grid-cols-1 gap-3 rounded-xl border border-border p-4 sm:grid-cols-12 sm:items-end"
                >
                    <FormField label="Produto ou Serviço" :error="form.errors[`items.${index}.product_id`]" class="sm:col-span-5">
                        <SelectInput
                            :model-value="item.product_id"
                            :options="productOptions"
                            placeholder="Selecione o item"
                            @update:model-value="onProductChange(item, $event)"
                        />
                    </FormField>

                    <FormField label="Qtd." :error="form.errors[`items.${index}.quantity`]" class="sm:col-span-2">
                        <input v-model="item.quantity" type="number" step="1" min="1" class="form-control">
                    </FormField>

                    <FormField label="Preço Unitário (R$)" :error="form.errors[`items.${index}.unit_price`]" class="sm:col-span-2">
                        <MoneyInput v-model="item.unit_price" />
                    </FormField>

                    <div class="flex items-center justify-between gap-2 sm:col-span-3 sm:h-10">
                        <p class="text-sm font-medium">{{ formatBRL(subtotal(item)) }}</p>
                        <button
                            type="button"
                            class="rounded-lg border border-border p-2 text-danger transition-colors hover:bg-danger/10"
                            :disabled="form.items.length === 1"
                            :class="{ 'cursor-not-allowed opacity-50': form.items.length === 1 }"
                            @click="removeItem(index)"
                        >
                            <Icon name="trash" class="size-4" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
                    <span class="text-sm text-secondary">Total Geral</span>
                    <span class="text-2xl font-semibold">{{ formatBRL(total) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
