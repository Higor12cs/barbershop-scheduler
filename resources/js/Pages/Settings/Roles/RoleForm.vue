<script setup>
import FormField from "../../../Components/FormField.vue";
import TextInput from "../../../Components/TextInput.vue";
import CheckboxInput from "../../../Components/CheckboxInput.vue";

const props = defineProps({
    form: { type: Object, required: true },
    groups: { type: Array, default: () => [] },
});

function isGroupChecked(group) {
    return group.permissions.every((permission) =>
        props.form.permissions.includes(permission.name),
    );
}

function toggleGroup(group, checked) {
    const names = group.permissions.map((permission) => permission.name);

    if (checked) {
        props.form.permissions = [
            ...new Set([...props.form.permissions, ...names]),
        ];
    } else {
        props.form.permissions = props.form.permissions.filter(
            (name) => !names.includes(name),
        );
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Dados do Papel</h2>
            </div>
            <div class="card-body">
                <FormField
                    label="Nome"
                    :error="form.errors.name"
                    class="max-w-sm"
                >
                    <TextInput v-model="form.name" autofocus />
                </FormField>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Permissões</h2>
            </div>
            <div class="card-body">
                <div
                    class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        v-for="group in groups"
                        :key="group.key"
                        class="rounded-xl border border-border p-4"
                    >
                        <div
                            class="flex items-center justify-between gap-2 border-b border-border pb-3"
                        >
                            <p class="text-sm font-semibold">
                                {{ group.label }}
                            </p>
                            <CheckboxInput
                                :model-value="isGroupChecked(group)"
                                label="Todos"
                                @update:model-value="toggleGroup(group, $event)"
                            />
                        </div>
                        <div class="flex flex-col gap-2 pt-3">
                            <CheckboxInput
                                v-for="permission in group.permissions"
                                :key="permission.name"
                                v-model="form.permissions"
                                :value="permission.name"
                                :label="permission.label"
                            />
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.permissions" class="form-error mt-2">
                    {{ form.errors.permissions }}
                </p>
            </div>
        </div>
    </div>
</template>
