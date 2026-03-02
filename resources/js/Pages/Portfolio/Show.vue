<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    project: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="project.title" />

    <div class="min-h-screen bg-gray-100 py-12">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <Link :href="route('portfolio.index')" class="text-sm text-gray-600 hover:text-gray-900">← Назад в портфолио</Link>

            <article class="mt-4 overflow-hidden rounded-lg bg-white shadow">
                <img
                    v-if="project.thumbnail_url"
                    :src="project.thumbnail_url"
                    :alt="project.title"
                    class="h-72 w-full object-cover"
                />
                <div class="p-6">
                    <h1 class="text-3xl font-bold text-gray-900">{{ project.title }}</h1>
                    <p v-if="project.summary" class="mt-3 text-gray-600">{{ project.summary }}</p>

                    <div v-if="project.content" class="prose mt-6 max-w-none whitespace-pre-line text-gray-700">
                        {{ project.content }}
                    </div>

                    <a
                        v-if="project.project_url"
                        :href="project.project_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-8 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Перейти к проекту
                    </a>
                </div>
            </article>
        </div>
    </div>
</template>
