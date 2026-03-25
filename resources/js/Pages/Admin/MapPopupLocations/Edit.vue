<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    rows: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    rows: props.rows.length
        ? props.rows.map((row) => ({
            id: row.id ?? null,
            land_id: row.land_id ?? '',
            title: row.title ?? '',
        }))
        : [{ id: null, land_id: '', title: '' }],
});

const addRow = () => {
    form.rows.push({
        id: null,
        land_id: '',
        title: '',
    });
};

const removeRow = (index) => {
    if (form.rows.length === 1) {
        return;
    }

    form.rows.splice(index, 1);
};

const submit = () => {
    form.put(route('admin.map-popup-locations.update'));
};
</script>

<template>
    <Head title="Заголовки popup карты" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Popup карты</h2>
                    <p class="mt-1 text-sm text-gray-500">Настройка соответствия path id и заголовка, который будет показан над логотипами проектов.</p>
                </div>
                <PrimaryButton @click="addRow" type="button">Добавить строку</PrimaryButton>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                        Используйте существующие id с карты на главной странице, например: land_44.
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(row, index) in form.rows"
                            :key="row.id ?? `new-${index}`"
                            class="grid gap-4 rounded-lg border border-gray-200 p-4 md:grid-cols-[minmax(0,220px)_minmax(0,1fr)_auto] md:items-start"
                        >
                            <div>
                                <InputLabel :for="`land_id_${index}`" value="Path ID" />
                                <TextInput :id="`land_id_${index}`" v-model="row.land_id" type="text" class="mt-1 block w-full" placeholder="land_44" />
                                <InputError class="mt-2" :message="form.errors[`rows.${index}.land_id`]" />
                            </div>

                            <div>
                                <InputLabel :for="`title_${index}`" value="Заголовок в popup" />
                                <TextInput :id="`title_${index}`" v-model="row.title" type="text" class="mt-1 block w-full" placeholder="Франция" />
                                <InputError class="mt-2" :message="form.errors[`rows.${index}.title`]" />
                            </div>

                            <div class="flex items-end md:pt-6">
                                <SecondaryButton
                                    type="button"
                                    @click="removeRow(index)"
                                    :disabled="form.rows.length === 1"
                                    class="w-full justify-center md:w-auto"
                                >
                                    Удалить
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-4 border-t border-gray-200 pt-6">
                        <p class="text-sm text-gray-500">Последнюю строку удалить нельзя, чтобы форма всегда оставалась доступной для ввода.</p>
                        <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>