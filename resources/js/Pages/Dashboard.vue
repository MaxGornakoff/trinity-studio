<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: {
        type: Object,
        default: () => ({ total: 0, published: 0 }),
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Админка студии</h2>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Всего проектов</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-6 shadow">
                        <p class="text-sm text-gray-500">Опубликовано</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ stats.published }}</p>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-lg font-semibold text-gray-900">Управление контентом</h3>
                    <p class="mt-2 text-sm text-gray-600">Создавайте и редактируйте страницы кейсов в портфолио студии.</p>
                    <div class="mt-4 flex gap-3">
                        <Link :href="route('admin.portfolio-projects.index')" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Перейти к проектам
                        </Link>
                        <Link :href="route('portfolio.index')" target="_blank" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Открыть портфолио
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
