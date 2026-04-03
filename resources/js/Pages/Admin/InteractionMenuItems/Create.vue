<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    parentOptions: {
        type: Array,
        default: () => [],
    },
});

const transliterate = (text) => {
    const ru = 'а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я'.split('-');
    const en = 'a-b-v-g-d-e-e-zh-z-i-y-k-l-m-n-o-p-r-s-t-u-f-h-ts-ch-sh-sch-_-y-_-e-yu-ya'.split('-');

    return text
        .toLowerCase()
        .split('')
        .map((char) => {
            const index = ru.indexOf(char);
            return index >= 0 ? en[index] : char;
        })
        .join('')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
};

const form = useForm({
    title: '',
    slug: '',
    parent_id: '',
    order_column: '0',
    is_active: true,
});

watch(() => form.title, (newTitle) => {
    if (!form.slug || form.slug === transliterate(form.title)) {
        form.slug = transliterate(newTitle);
    }
});

const submit = () => {
    form.post(route('admin.interaction-menu-items.store'));
};
</script>

<template>
    <Head title="Новый пункт меню" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Добавить пункт меню</h2>
                <Link :href="route('admin.interaction-menu-items.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    Назад к списку
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                        Сейчас на лендинг выводятся только корневые пункты. Но структура сразу поддерживает до 4 уровней вложенности.
                    </div>

                    <div>
                        <InputLabel for="title" value="Название" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug" />
                        <TextInput id="slug" v-model="form.slug" type="text" class="mt-1 block w-full" />
                        <p class="mt-1 text-xs text-gray-500">Заполняется автоматически, но можно изменить вручную.</p>
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>

                    <div>
                        <InputLabel for="parent_id" value="Родительский пункт" />
                        <select id="parent_id" v-model="form.parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Без родителя, уровень 1</option>
                            <option v-for="option in props.parentOptions" :key="option.id" :value="String(option.id)">
                                {{ option.title }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.parent_id" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="order_column" value="Порядок сортировки" />
                            <TextInput id="order_column" v-model="form.order_column" type="number" min="0" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.order_column" />
                        </div>

                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                Активный пункт
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-6">
                        <Link :href="route('admin.interaction-menu-items.index')" class="text-sm text-gray-600 hover:text-gray-900">
                            Отмена
                        </Link>
                        <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
