<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    title: props.project.title ?? '',
    slug: props.project.slug ?? '',
    summary: props.project.summary ?? '',
    content: props.project.content ?? '',
    thumbnail_url: props.project.thumbnail_url ?? '',
    project_url: props.project.project_url ?? '',
    order_column: String(props.project.order_column ?? 0),
    is_featured: !!props.project.is_featured,
    is_published: !!props.project.is_published,
    published_at: props.project.published_at ? props.project.published_at.slice(0, 16) : '',
});

const submit = () => {
    form.put(route('admin.portfolio-projects.update', props.project.id));
};
</script>

<template>
    <Head title="Редактирование проекта" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Редактировать проект</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                    <div>
                        <InputLabel for="title" value="Название" />
                        <TextInput id="title" v-model="form.title" type="text" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>

                    <div>
                        <InputLabel for="slug" value="Slug" />
                        <TextInput id="slug" v-model="form.slug" type="text" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>

                    <div>
                        <InputLabel for="summary" value="Краткое описание" />
                        <textarea id="summary" v-model="form.summary" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="3" />
                        <InputError class="mt-2" :message="form.errors.summary" />
                    </div>

                    <div>
                        <InputLabel for="content" value="Контент страницы проекта" />
                        <textarea id="content" v-model="form.content" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" rows="10" />
                        <InputError class="mt-2" :message="form.errors.content" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="thumbnail_url" value="URL обложки" />
                            <TextInput id="thumbnail_url" v-model="form.thumbnail_url" type="url" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.thumbnail_url" />
                        </div>

                        <div>
                            <InputLabel for="project_url" value="URL проекта" />
                            <TextInput id="project_url" v-model="form.project_url" type="url" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.project_url" />
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="order_column" value="Порядок сортировки" />
                            <TextInput id="order_column" v-model="form.order_column" type="number" min="0" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.order_column" />
                        </div>

                        <div>
                            <InputLabel for="published_at" value="Дата публикации" />
                            <TextInput id="published_at" v-model="form.published_at" type="datetime-local" class="mt-1 block w-full" />
                            <InputError class="mt-2" :message="form.errors.published_at" />
                        </div>
                    </div>

                    <div class="flex gap-6">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_featured" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                            Избранный проект
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input v-model="form.is_published" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                            Опубликовать
                        </label>
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">Сохранить</PrimaryButton>
                        <Link :href="route('admin.portfolio-projects.index')" class="text-sm text-gray-600 hover:text-gray-900">Отмена</Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
