<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Портфолио" />

    <div class="min-h-screen bg-gray-100 py-12">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900">Портфолио студии</h1>
                <Link :href="route('home')" class="text-sm text-gray-600 hover:text-gray-900">На главную</Link>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <article v-for="project in projects" :key="project.id" class="overflow-hidden rounded-lg bg-white shadow">
                    <img
                        v-if="project.thumbnail_url"
                        :src="project.thumbnail_url"
                        :alt="project.title"
                        class="h-48 w-full object-cover"
                    />
                    <div class="p-5">
                        <h2 class="text-xl font-semibold text-gray-900">{{ project.title }}</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ project.summary }}</p>
                        <Link :href="route('portfolio.show', project.slug)" class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-700">
                            Открыть кейс
                        </Link>
                    </div>
                </article>
            </div>

            <p v-if="projects.length === 0" class="rounded-lg bg-white p-10 text-center text-sm text-gray-500 shadow">
                Портфолио пока не заполнено.
            </p>
        </div>
    </div>
</template>
