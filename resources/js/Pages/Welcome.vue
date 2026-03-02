<script setup>
import { Head, Link } from '@inertiajs/vue3';

const services = [
    {
        title: 'Дизайн и редизайн',
        text: 'Интуитивно понятный и привлекательный интерфейс, созданный с учётом задач бренда и аудитории.',
    },
    {
        title: 'Разработка',
        text: 'Полнофункциональные, адаптивные и быстрые сайты под ключ — от идеи до запуска.',
    },
    {
        title: 'Сопровождение',
        text: 'Надёжное сопровождение, обновления и оперативное решение технических вопросов.',
    },
    {
        title: 'SEO-оптимизация',
        text: 'Выводим сайт в топ поисковой выдачи, чтобы клиенты находили вас сами.',
    },
    {
        title: 'Интеграции',
        text: 'Подключаем сайт к CRM, ERP и бизнес-сервисам для автоматизации процессов.',
    },
    {
        title: 'Маркетплейсы',
        text: 'Автоматизируем выгрузку товаров на Wildberries, Ozon, Я.Маркет и другие площадки.',
    },
];

const projectCards = [
    { title: 'Taxymatch covoiturage', tone: 'from-indigo-500 to-blue-500' },
    { title: 'First Class Yachting', tone: 'from-cyan-500 to-blue-600' },
    { title: 'Fromane', tone: 'from-neutral-500 to-neutral-700' },
    { title: 'Debenhams', tone: 'from-pink-500 to-fuchsia-600' },
    { title: 'PPIZZA', tone: 'from-stone-300 to-stone-500' },
    { title: 'MTransfer', tone: 'from-slate-600 to-slate-900' },
    { title: 'VEF Kvartāls', tone: 'from-zinc-700 to-neutral-900' },
    { title: 'My Nomad Tales', tone: 'from-sky-200 to-slate-400' },
    { title: 'SVALSON', tone: 'from-stone-200 to-gray-400' },
    { title: 'Bamboo laser', tone: 'from-emerald-50 to-gray-300' },
    { title: 'Rozenbergs', tone: 'from-zinc-100 to-zinc-300' },
    { title: 'CMB Housing solutions', tone: 'from-teal-300 to-cyan-600' },
];

const mapRows = [
    '000000000000000000000000000000000000000000000000',
    '000000000000001111100000000000000000000000000000',
    '000000000000111111111000000001111000000000000000',
    '000000000011111111111110000111111111000000000000',
    '000000001111111111111111011111111111110000000000',
    '000000011111111111111111111111111111111000000000',
    '000001111111111111111111111111111111111100000000',
    '000011111111111111111111111111111111111111000000',
    '000011111111111111111111111111111111111111000000',
    '000001111111111111111111111111111111111110000000',
    '000000111111111111111111111111111111111100000000',
    '000000011111111111111111111111111111111000000000',
    '000000001111111111111111111111111111110000000000',
    '000000000111111111111111111111111111100000000000',
    '000000000011111111111111111111111111000000000000',
    '000000000001111111111111111111111110000000000000',
    '000000000000111111111111111111111100000000000000',
    '000000000000001111111111111111110000000000000000',
    '000000000000000011111111111111000000000000000000',
    '000000000000000000111111111100000000000000000000',
];

const highlightedMapDots = new Set([
    '4-8',
    '7-7',
    '8-11',
    '10-16',
    '9-23',
    '12-28',
    '8-33',
]);

const mapCols = mapRows[0].length;
const mapDots = mapRows.flatMap((row, rowIndex) =>
    row.split('').map((value, colIndex) => ({
        key: `${rowIndex}-${colIndex}`,
        active: value === '1',
        highlighted: highlightedMapDots.has(`${rowIndex}-${colIndex}`),
    }))
);

defineProps({
    canLogin: {
        type: Boolean,
    },
    featuredProjects: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head title="Trinity Studio" />

    <div class="min-h-screen bg-gray-100 font-sans text-gray-900 tracking-tight">
        <header class="pt-4">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="rounded-sm border border-gray-400 bg-white px-2 py-0.5 text-[10px] font-semibold tracking-[0.18em]">
                    TRINITY WEB
                </div>

                <nav class="hidden items-center gap-5 text-[11px] font-medium text-gray-600 sm:flex">
                    <a href="#projects" class="hover:text-gray-900">Проекты</a>
                    <a href="#services" class="hover:text-gray-900">Услуги</a>
                    <a href="#about" class="hover:text-gray-900">О нас</a>
                </nav>

                <div class="flex items-center gap-3">
                    <a href="#contacts" class="rounded-full bg-gray-800 px-4 py-1.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-white hover:bg-gray-700">
                        Контакт
                    </a>
                    <template v-if="canLogin && $page.props.auth.user">
                        <Link :href="route('dashboard')" class="text-[11px] font-medium text-gray-600 hover:text-gray-900">Админка</Link>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="mx-auto max-w-6xl px-4 pb-28 pt-16 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl font-bold leading-[0.95] tracking-[-0.03em] sm:text-6xl md:text-7xl">Создаём сайты,<br />которые работают</h1>
                    <p class="mx-auto mt-5 max-w-2xl text-[12px] leading-relaxed text-gray-500 sm:text-[13px]" id="about">
                        Помогаем клиентам выйти в интернет: сайт, дизайн, реклама — легко и эффективно.
                    </p>
                </div>

                <div class="relative mx-auto mt-12 max-w-4xl">
                    <div class="rounded-2xl border border-gray-300 bg-gradient-to-b from-gray-200 to-gray-300 p-5 shadow-sm">
                        <div class="aspect-[16/9] overflow-hidden rounded-lg bg-gradient-to-br from-sky-700 via-blue-700 to-slate-800">
                            <div class="h-full w-full bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.2),_transparent_40%)]" />
                        </div>
                    </div>
                    <div class="mx-auto h-3 w-[88%] rounded-b-full bg-gray-300" />
                    <div class="absolute -bottom-8 right-10 w-40 rounded-3xl border-4 border-gray-300 bg-gradient-to-b from-blue-700 to-slate-900 p-2 shadow-md sm:w-48">
                        <div class="aspect-[9/17] rounded-2xl bg-gradient-to-b from-sky-700 via-blue-700 to-slate-900" />
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8" id="services">
                <div class="grid gap-8 sm:grid-cols-2 sm:items-end">
                    <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-5xl">Что мы делаем?</h2>
                    <p class="max-w-md text-[13px] leading-relaxed text-gray-500 sm:justify-self-end">
                        Мы создаём цифровые решения, которые работают на ваш бизнес: от дизайна и разработки до сопровождения,
                        продвижения и рекламы.
                    </p>
                </div>

                <div class="mt-10 flex items-center gap-5 text-2xl font-semibold sm:text-[30px]">
                    <span>Услуги</span>
                    <span class="text-gray-400">FAQ</span>
                </div>

                <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    <article v-for="service in services" :key="service.title" class="rounded-lg bg-gray-800 p-6 text-white">
                        <h3 class="text-lg font-semibold leading-tight sm:text-[22px]">{{ service.title }}</h3>
                        <p class="mt-3 text-[12px] leading-relaxed text-gray-300 sm:text-[13px]">{{ service.text }}</p>
                    </article>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 sm:items-end">
                    <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-5xl">География проектов</h2>
                    <p class="max-w-md text-[13px] leading-relaxed text-gray-500 sm:justify-self-end">
                        Мы работаем с клиентами по всей России и за её пределами, независимо от вашего местоположения.
                    </p>
                </div>

                <div class="mt-10 rounded-2xl border border-gray-200 bg-white px-6 py-10">
                    <div
                        class="mx-auto grid max-w-4xl gap-1"
                        :style="{ gridTemplateColumns: `repeat(${mapCols}, minmax(0, 1fr))` }"
                    >
                        <div
                            v-for="dot in mapDots"
                            :key="dot.key"
                            class="h-1.5 w-1.5 rounded-full"
                            :class="dot.active ? (dot.highlighted ? 'bg-amber-400' : 'bg-gray-500') : 'bg-transparent'"
                        />
                    </div>
                </div>

                <div class="mt-10 flex justify-center gap-8 text-2xl font-semibold sm:text-[32px]">
                    <span class="text-gray-400">Россия</span>
                    <span>Европа</span>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8" id="projects">
                <div class="grid gap-x-6 gap-y-8 md:grid-cols-2">
                    <article v-for="project in projectCards" :key="project.title" class="overflow-hidden rounded-xl">
                        <div class="aspect-[16/9] rounded-xl bg-gradient-to-br" :class="project.tone" />
                        <h3 class="mt-3 text-2xl font-semibold leading-tight sm:text-[36px]">{{ project.title }}</h3>
                    </article>
                </div>

                <div v-if="featuredProjects.length > 0" class="mt-14 border-t border-gray-300 pt-10">
                    <h3 class="mb-4 text-xl font-semibold tracking-[-0.01em] sm:text-2xl">Живые кейсы из БД</h3>
                    <div class="grid gap-5 md:grid-cols-3">
                        <article v-for="project in featuredProjects" :key="project.id" class="overflow-hidden rounded-lg bg-white shadow-sm">
                            <img v-if="project.thumbnail_url" :src="project.thumbnail_url" :alt="project.title" class="h-36 w-full object-cover" />
                            <div class="p-4">
                                <h4 class="text-base font-semibold">{{ project.title }}</h4>
                                <Link :href="route('portfolio.show', project.slug)" class="mt-2 inline-flex text-sm font-medium text-gray-700 hover:text-gray-900">
                                    Открыть кейс
                                </Link>
                            </div>
                        </article>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-32 sm:px-6 lg:px-8" id="contacts">
                <div class="grid gap-8 sm:grid-cols-2 sm:items-end">
                    <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-5xl">Взаимодействие</h2>
                    <p class="max-w-md text-[13px] leading-relaxed text-gray-500 sm:justify-self-end">
                        Мы берём рутину на себя — сбор данных, подготовку, организацию. Вы фокусируетесь на главном,
                        а мы доводим процесс до результата.
                    </p>
                </div>

                <div class="mt-14 flex justify-center">
                    <div class="relative h-80 w-[42rem] max-w-full">
                        <div class="absolute inset-0 rounded-full border border-gray-300" />
                        <div class="absolute inset-8 rounded-full border border-gray-300" />
                        <div class="absolute inset-16 rounded-full border border-gray-300" />

                        <div class="absolute left-1/2 top-1/2 h-16 w-16 -translate-x-1/2 -translate-y-1/2 rounded-full border-8 border-gray-300 bg-gray-800" />

                        <div class="absolute left-0 top-1/2 -translate-y-1/2 text-2xl font-semibold sm:text-3xl">Старт •</div>
                        <div class="absolute right-0 top-1/2 -translate-y-1/2 text-2xl font-semibold sm:text-3xl">• Финиш</div>

                        <span class="absolute left-20 top-14 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">Маркетплейсы</span>
                        <span class="absolute left-28 top-24 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">Сопровождение</span>
                        <span class="absolute left-32 top-34 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">Разработка</span>
                        <span class="absolute left-32 top-44 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">Web-дизайн</span>
                        <span class="absolute left-28 top-54 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">Интеграции</span>
                        <span class="absolute left-20 top-64 rounded-full bg-gray-800 px-3 py-1 text-[11px] text-white">SEO-оптимизация</span>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
