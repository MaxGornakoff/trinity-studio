<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    items: {
        type: Array,
        default: () => [],
    },
});

const destroyItem = (item) => {
    if (item.children_count > 0) {
        alert('Сначала удалите или перенесите дочерние пункты.');
        return;
    }

    if (!confirm(`Удалить пункт меню "${item.title}"?`)) {
        return;
    }

    router.delete(route('admin.interaction-menu-items.destroy', item.id));
};
</script>

<template>
    <Head title="Меню взаимодействия" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Меню взаимодействия</h2>
                    <p class="mt-1 text-sm text-gray-500">Дерево до 4 уровней. На главной сейчас используется первый уровень.</p>
                </div>
                <Link
                    :href="route('admin.interaction-menu-items.create')"
                    class="inline-flex items-center rounded-md border border-transparent bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-gray-700"
                >
                    Добавить пункт
                </Link>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Пункт</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Slug</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Уровень</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Родитель</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Порядок</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Статус</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Действия</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="item in items" :key="item.id">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ item.slug }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ item.level }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ item.parent_title || 'Корневой пункт' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ item.order_column }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium"
                                        :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700'"
                                    >
                                        {{ item.is_active ? 'Активен' : 'Скрыт' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right text-sm space-x-4">
                                    <Link :href="route('admin.interaction-menu-items.edit', item.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Изменить
                                    </Link>
                                    <button type="button" class="text-red-600 hover:text-red-900" @click="destroyItem(item)">
                                        Удалить
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="items.length === 0">
                                <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Пока нет пунктов меню. Добавьте первый корневой пункт.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
