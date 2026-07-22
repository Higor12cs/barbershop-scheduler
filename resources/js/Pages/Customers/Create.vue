<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import Icon from "../../Components/Icon.vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import CustomerForm from "./CustomerForm.vue";

const form = useForm({
    name: "",
    phone: "",
    email: "",
    birth_date: "",
    notes: "",
    active: true,
});

function submit() {
    form.post(route("customers.store"));
}
</script>

<template>
    <AppLayout>
        <Head title="Novo Cliente" />

        <form class="space-y-6" @submit.prevent="submit">
            <div class="page-header">
                <div>
                    <h1 class="page-header-title">Criar Novo Cliente</h1>
                    <p class="page-header-subtitle">
                        Cadastre um novo cliente da barbearia.
                    </p>
                </div>
                <div class="page-header-actions">
                    <Link
                        :href="route('customers.index')"
                        class="btn btn-secondary"
                        >Cancelar</Link
                    >
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <Icon name="check" class="size-4" />
                        {{ form.processing ? "Salvando..." : "Salvar Cliente" }}
                    </button>
                </div>
            </div>

            <CustomerForm :form="form" />
        </form>
    </AppLayout>
</template>
