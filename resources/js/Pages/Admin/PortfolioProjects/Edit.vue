<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    propertiesOptions: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    title: props.project.title ?? '',
    slug: props.project.slug ?? '',
    summary: props.project.summary ?? '',
    content: props.project.content ?? '',
    thumbnail_url: props.project.thumbnail_url ?? '',
    desktop_mockup_image: null,
    mobile_mockup_image: null,
    logo_image: null,
    project_url: props.project.project_url ?? '',
    order_column: String(props.project.order_column ?? 0),
    is_featured: !!props.project.is_featured,
    is_published: !!props.project.is_published,
    show_in_slider: !!props.project.show_in_slider,
    region: props.project.region ?? 'russia',
    map_land_id: props.project.map_land_id ?? '',
    properties: props.project.properties ?? [],
    published_at: props.project.published_at ? props.project.published_at.slice(0, 16) : '',
});

const toggleProperty = (property) => {
    if (form.properties.includes(property)) {
        form.properties = form.properties.filter((item) => item !== property);
        return;
    }

    form.properties = [...form.properties, property];
};

const desktopPreview = ref(props.project.desktop_mockup_image || null);
const mobilePreview = ref(props.project.mobile_mockup_image || null);
const logoPreview = ref(props.project.logo_image || null);

// Функция транслитерации
const transliterate = (text) => {
    const ru = 'а-б-в-г-д-е-ё-ж-з-и-й-к-л-м-н-о-п-р-с-т-у-ф-х-ц-ч-ш-щ-ъ-ы-ь-э-ю-я'.split('-');
    const en = 'a-b-v-g-d-e-e-zh-z-i-y-k-l-m-n-o-p-r-s-t-u-f-h-ts-ch-sh-sch-_-y-_-e-yu-ya'.split('-');
    
    return text
        .toLowerCase()
        .split('')
        .map(char => {
            const index = ru.indexOf(char);
            return index >= 0 ? en[index] : char;
        })
        .join('')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
};

// Автоматическая транслитерация при вводе названия
let manuallyEditedSlug = false;
watch(() => form.slug, () => {
    if (form.slug !== transliterate(form.title)) {
        manuallyEditedSlug = true;
    }
});

watch(() => form.title, (newTitle) => {
    if (!manuallyEditedSlug) {
        form.slug = transliterate(newTitle);
    }
});

const handleDesktopImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.desktop_mockup_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            desktopPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleMobileImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.mobile_mockup_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            mobilePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleLogoImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.logo_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeDesktopImage = () => {
    form.desktop_mockup_image = null;
    desktopPreview.value = null;
};

const removeMobileImage = () => {
    form.mobile_mockup_image = null;
    mobilePreview.value = null;
};

const removeLogoImage = () => {
    form.logo_image = null;
    logoPreview.value = null;
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: 'PUT',
    })).post(route('admin.portfolio-projects.update', props.project.id), {
        forceFormData: true,
    });
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
                        <InputLabel for="slug" value="ID Проекта" />
                        <TextInput id="slug" v-model="form.slug" type="text" class="mt-1 block w-full" />
                        <p class="mt-1 text-xs text-gray-500">Автоматически создается из названия. Можно изменить вручную.</p>
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="desktop_mockup_image" value="Изображение для десктопного мокапа" />
                            <input 
                                id="desktop_mockup_image" 
                                type="file" 
                                accept="image/*"
                                @change="handleDesktopImageChange"
                                class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-gray-900 file:text-white
                                    hover:file:bg-gray-800
                                    cursor-pointer"
                            />
                            <InputError class="mt-2" :message="form.errors.desktop_mockup_image" />
                            
                            <div v-if="desktopPreview" class="mt-4 relative inline-block">
                                <img :src="desktopPreview" alt="Desktop preview" class="max-h-[300px] w-auto rounded-lg border border-gray-300 object-contain" />
                                <button 
                                    type="button"
                                    @click="removeDesktopImage"
                                    class="absolute -top-2 -right-2 rounded-full bg-red-500 text-white w-6 h-6 flex items-center justify-center hover:bg-red-600"
                                >
                                    ×
                                </button>
                            </div>
                        </div>

                        <div>
                            <InputLabel for="mobile_mockup_image" value="Изображение для мобильного мокапа" />
                            <input 
                                id="mobile_mockup_image" 
                                type="file" 
                                accept="image/*"
                                @change="handleMobileImageChange"
                                class="mt-1 block w-full text-sm text-gray-500
                                    file:mr-4 file:py-2 file:px-4
                                    file:rounded-md file:border-0
                                    file:text-sm file:font-semibold
                                    file:bg-gray-900 file:text-white
                                    hover:file:bg-gray-800
                                    cursor-pointer"
                            />
                            <InputError class="mt-2" :message="form.errors.mobile_mockup_image" />
                            
                            <div v-if="mobilePreview" class="mt-4 relative inline-block">
                                <img :src="mobilePreview" alt="Mobile preview" class="max-h-[300px] w-auto rounded-lg border border-gray-300 object-contain" />
                                <button 
                                    type="button"
                                    @click="removeMobileImage"
                                    class="absolute -top-2 -right-2 rounded-full bg-red-500 text-white w-6 h-6 flex items-center justify-center hover:bg-red-600"
                                >
                                    ×
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <InputLabel for="map_land_id" value="ID точки на карте (например: land_44)" />
                        <TextInput id="map_land_id" v-model="form.map_land_id" type="text" class="mt-1 block w-full" />
                        <p class="mt-1 text-xs text-gray-500">Укажи id path из карты главной страницы в формате land_число.</p>
                        <InputError class="mt-2" :message="form.errors.map_land_id" />
                    </div>

                    <div>
                        <InputLabel for="logo_image" value="Логотип проекта" />
                        <input
                            id="logo_image"
                            type="file"
                            accept="image/*"
                            @change="handleLogoImageChange"
                            class="mt-1 block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-md file:border-0
                                file:text-sm file:font-semibold
                                file:bg-gray-900 file:text-white
                                hover:file:bg-gray-800
                                cursor-pointer"
                        />
                        <InputError class="mt-2" :message="form.errors.logo_image" />

                        <div v-if="logoPreview" class="mt-4 relative inline-block">
                            <img :src="logoPreview" alt="Logo preview" class="max-h-[140px] w-auto rounded-lg border border-gray-300 object-contain bg-white p-2" />
                            <button
                                type="button"
                                @click="removeLogoImage"
                                class="absolute -top-2 -right-2 rounded-full bg-red-500 text-white w-6 h-6 flex items-center justify-center hover:bg-red-600"
                            >
                                ×
                            </button>
                        </div>
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
                            <InputLabel for="project_url" value="Ссылка на сайт проекта" />
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
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.show_in_slider" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm" />
                                Добавить в слайдер
                            </label>
                    </div>

                    <div>
                        <InputLabel value="География проекта" />
                        <div class="mt-2 flex gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.region" type="radio" value="russia" class="border-gray-300 text-indigo-600 shadow-sm" />
                                Россия
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input v-model="form.region" type="radio" value="world" class="border-gray-300 text-indigo-600 shadow-sm" />
                                Мир
                            </label>
                        </div>
                        <InputError class="mt-2" :message="form.errors.region" />
                    </div>

                    <div>
                        <InputLabel value="Свойства проекта (теги)" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="property in props.propertiesOptions"
                                :key="property"
                                type="button"
                                @click="toggleProperty(property)"
                                class="rounded-full border px-3 py-1 text-sm transition"
                                :class="form.properties.includes(property)
                                    ? 'border-gray-900 bg-gray-900 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400'"
                            >
                                {{ property }}
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.properties" />
                        <InputError class="mt-1" :message="form.errors['properties.0']" />
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
