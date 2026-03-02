<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const destroyProject = (project) => {
    if (!confirm(`Удалить проект "${project.title}"?`)) {
        return;
    }

    router.delete(route('admin.portfolio-projects.destroy', project.id));
};
</script>

<template>
    <Head title="Portfolio Admin" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Портфолио</h2>
                <Link
                    :href="route('admin.portfolio-projects.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                >
                    Добавить проект
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Проект</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Slug</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Статус</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="project in projects" :key="project.id">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ project.title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ project.slug }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium"
                                        :class="project.is_published ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                                    >
                                        {{ project.is_published ? 'Опубликован' : 'Черновик' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-4">
                                    <Link :href="route('portfolio.show', project.slug)" class="text-gray-600 hover:text-gray-900" target="_blank">
                                        Смотреть
                                    </Link>
                                    <Link :href="route('admin.portfolio-projects.edit', project.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Изменить
                                    </Link>
                                    <button type="button" class="text-red-600 hover:text-red-900" @click="destroyProject(project)">
                                        Удалить
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="projects.length === 0">
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Пока нет проектов. Добавьте первый проект портфолио.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
