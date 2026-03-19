<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';

const slideContentRef = ref(null);
const scrollImageRef = ref(null);
const scrollOffset = ref(50);
const isSliderHovered = ref(false);
const currentSlideLink = ref('/');
const currentSlideIndex = ref(0);
const defaultDesktopImage = '/images/1class.jpg';
const defaultMobileImage = '/images/1class-m.jpg';
const currentDesktopImage = ref(defaultDesktopImage);
const currentMobileImage = ref(defaultMobileImage);

const slideContentMobileRef = ref(null);
const scrollImageMobileRef = ref(null);
const scrollOffsetMobile = ref(50);
const isMobileHovered = ref(false);
const isTransitioning = ref(false);
const transitionDirection = ref('next');
const isPreparingSlide = ref(false);
const isPreloadLagging = ref(false);

const desktopContainerRef = ref(null);
const mobileContainerRef = ref(null);

const TRANSITION_MS = 600;
const PRELOAD_MAX_WAIT_MS = 350;

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

const props = defineProps({
    canLogin: {
        type: Boolean,
    },
    featuredProjects: {
        type: Array,
        default: () => [],
    },
    sliderProjects: {
        type: Array,
        default: () => [],
    },
});

// tabs state
const activeTab = ref('services');
const switchTab = (tab) => {
    activeTab.value = tab;
};

// services hover state
const hoveredService = ref(null);

// cursor spotlight effect for services
const servicesContainerRef = ref(null);
const spotlightX = ref(0);
const spotlightY = ref(0);
const showSpotlight = ref(false);

const handleServicesMouseMove = (e) => {
    if (!servicesContainerRef.value) return;
    const rect = servicesContainerRef.value.getBoundingClientRect();
    spotlightX.value = e.clientX - rect.left;
    spotlightY.value = e.clientY - rect.top;
    
    // Apply light text effect to elements under spotlight
    const cards = servicesContainerRef.value.querySelectorAll('.service-card');
    cards.forEach(card => {
        const cardRect = card.getBoundingClientRect();
        const cardX = cardRect.left - rect.left + cardRect.width / 2;
        const cardY = cardRect.top - rect.top + cardRect.height / 2;
        
        // Apply gradient with light text under spotlight, white text outside
        const gradX = spotlightX.value;
        const gradY = spotlightY.value;
        
        card.style.setProperty('--grad-x', gradX + 'px');
        card.style.setProperty('--grad-y', gradY + 'px');
    });
};

const handleServicesMouseEnter = () => {
    showSpotlight.value = true;
};

const handleServicesMouseLeave = () => {
    showSpotlight.value = false;
    hoveredService.value = null;
};

// FAQ items with expandable state
const faqs = ref([
    {
        question: 'Какой подход к дизайну сайта обеспечивает лучшую конверсию?',
        answer: `Эффективный дизайн строится на принципах UX-ориентированности:
<ul class="list-disc list-inside">
    <li>Чёткая визуальная иерархия и понятная навигация</li>
    <li>Адаптивность под все устройства (mobile-first)</li>
    <li>Быстрая загрузка контента (оптимизированные изображения, минимизация скриптов)</li>
    <li>Призывы к действию (CTA), выделенные контрастом и расположением</li>
    <li>Тестирование гипотез через A/B-тесты и тепловые карты</li>
</ul>
<p>Результат: снижение показателя отказов и рост конверсии на 20–40%.</p>`,
        open: false,
    },
    {
        question: 'Какую технологию выбрать для разработки?',
        answer: `<p>Выбор зависит от задач проекта (в виде таблицы):</p>
<table class="w-full text-left border-collapse">
    <thead>
        <tr>
            <th class="border px-2 py-1">Задача:</th>
            <th class="border px-2 py-1">Рекомендуемое решение:</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="border px-2 py-1">Лендинг, блог, контент-сайт</td>
            <td class="border px-2 py-1">Next.js (SSR/SSG) + TypeScript</td>
        </tr>
        <tr>
            <td class="border px-2 py-1">SPA, дашборд, сложная интерактивность</td>
            <td class="border px-2 py-1">React + Vite + Zustand/Redux</td>
        </tr>
        <tr>
            <td class="border px-2 py-1">Простой сайт с формой заявки</td>
            <td class="border px-2 py-1">Статический сайт или готовый CMS</td>
        </tr>
        <tr>
            <td class="border px-2 py-1">Высокая нагрузка, масштабируемость</td>
            <td class="border px-2 py-1">Next.js + Node.js + кэширование (Redis)</td>
        </tr>
    </tbody>
</table>
<p>Совет: Начинайте с минимально жизнеспособного продукта (MVP), чтобы проверить гипотезы до масштабных вложений.</p>`,
        open: false,
    },
    {
        question: 'Что входит в техническое сопровождение сайта?',
        answer: `<p>Стандартный пакет сопровождения включает:</p>
<ul class="list-disc list-inside">
    <li>✅ Мониторинг работоспособности (24/7) и резервное копирование</li>
    <li>✅ Обновление ядра, плагинов, зависимостей (ежемесячно)</li>
    <li>✅ Исправление багов и уязвимостей безопасности</li>
    <li>✅ Оптимизация скорости загрузки (раз в квартал)</li>
    <li>✅ Консультации по добавлению нового контента</li>
</ul>
<p>Рекомендация: Контент стоит обновлять минимум 1–2 раза в месяц — это положительно влияет на SEO и удержание аудитории.</p>`,
        open: false,
    },
    {
        question: 'Какие 3 шага дадут максимальный эффект для SEO нового сайта?',
        answer: `<p>Приоритетные действия:</p>
<ol class="list-decimal list-inside">
    <li>Техническая база: корректная структура URL, robots.txt, sitemap.xml, микроразметка Schema.org, HTTPS, скорость загрузки &lt;2.5 с</li>
    <li>Контент-стратегия: семантическое ядро (ключевые запросы), уникальные тексты с пользой для пользователя, регулярное обновление</li>
    <li>Внешние сигналы: качественная ссылочная масса (гостевые посты, каталоги, партнёрства), упоминания в соцсетях</li>
</ol>
<p>Важно: Первые устойчивые результаты в органике обычно появляются через 3–6 месяцев системной работы.</p>`,
        open: false,
    },
    {
        question: 'Как безопасно интегрировать сайт с внешними сервисами?',
        answer: `<p>Ключевые принципы безопасной интеграции:</p>
<ul class="list-disc list-inside">
    <li>Использование OAuth 2.0 / API-ключей с ограниченным доступом (scopes)</li>
    <li>Хранение секретов в переменных окружения (не в коде!)</li>
    <li>Валидация и санитизация всех входящих данных</li>
    <li>Логирование запросов и мониторинг аномалий</li>
    <li>Регулярный аудит зависимостей (npm audit, Snyk, Dependabot)</li>
</ul>
<p>Пример: Для подключения платежного шлюза используйте webhook-подписи и проверяйте подпись ответа от провайдера.</p>`,
        open: false,
    },
    {
        question: 'Как согласовать работу специалистов, чтобы избежать переделок?',
        answer: `<p>Эффективная коллаборация строится на:</p>
<ul class="list-disc list-inside">
    <li>Едином брифе с целями, метриками успеха и ограничениями</li>
    <li>Прототипировании (Figma + комментарии) до начала разработки</li>
    <li>Чек-листах для передачи макетов в вёрстку (адаптивы, состояния, анимации)</li>
    <li>Раннем подключении SEO: мета-теги, структура заголовков, ЧПУ — закладываются на этапе дизайна</li>
    <li>Еженедельным синкам и использованию трекера задач (Jira, Linear, Notion)</li>
</ul>
<p>Результат: сокращение времени на доработки на 30–50% и более предсказуемый релиз.</p>`,
        open: false,
    },
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};

const getSlideData = (index) => {
    if (!props.sliderProjects.length) {
        return {
            index: 0,
            project_url: '/',
            desktop_mockup_image: defaultDesktopImage,
            mobile_mockup_image: defaultMobileImage,
        };
    }

    const normalizedIndex = (index + props.sliderProjects.length) % props.sliderProjects.length;
    const project = props.sliderProjects[normalizedIndex];

    return {
        index: normalizedIndex,
        project_url: project.project_url || '/',
        desktop_mockup_image: project.desktop_mockup_image || defaultDesktopImage,
        mobile_mockup_image: project.mobile_mockup_image || defaultMobileImage,
    };
};

const preloadImage = (src) => new Promise((resolve) => {
    if (!src) {
        resolve();
        return;
    }

    let settled = false;
    const done = () => {
        if (settled) {
            return;
        }
        settled = true;
        resolve();
    };

    const img = new Image();
    img.onload = done;
    img.onerror = done;
    img.src = src;

    if (typeof img.decode === 'function') {
        img.decode().then(done).catch(done);
    }
});

const wait = (ms) => new Promise((resolve) => {
    setTimeout(resolve, ms);
});

const applyCurrentSlide = (index) => {
    const nextSlide = getSlideData(index);

    currentSlideIndex.value = nextSlide.index;
    currentSlideLink.value = nextSlide.project_url;
    currentDesktopImage.value = nextSlide.desktop_mockup_image;
    currentMobileImage.value = nextSlide.mobile_mockup_image;

    nextTick(() => {
        calculateScrollOffset();
        calculateScrollOffsetMobile();
    });
};

const runSlideTransition = async (targetIndex, direction) => {
    if (props.sliderProjects.length < 2 || isTransitioning.value || isPreparingSlide.value) {
        return;
    }

    isTransitioning.value = true;
    transitionDirection.value = direction;

    const nextSlide = getSlideData(targetIndex);
    const preloadPromise = Promise.all([
        preloadImage(nextSlide.desktop_mockup_image),
        preloadImage(nextSlide.mobile_mockup_image),
    ]);

    // Сбрасываем анимацию скролла перед выездом
    if (scrollImageRef.value) {
        scrollImageRef.value.style.transform = 'translateY(0)';
    }
    if (scrollImageMobileRef.value) {
        scrollImageMobileRef.value.style.transform = 'translateY(0)';
    }

    const desktopOutClass = direction === 'next' ? 'transitioning-out-next' : 'transitioning-out-prev';
    const desktopInClass = direction === 'next' ? 'transitioning-in-next' : 'transitioning-in-prev';

    if (desktopContainerRef.value) {
        desktopContainerRef.value.classList.add(desktopOutClass);
    }
    if (mobileContainerRef.value) {
        mobileContainerRef.value.classList.add('transitioning-out');
    }

    setTimeout(async () => {
        isPreparingSlide.value = true;
        const preloadState = await Promise.race([
            preloadPromise.then(() => 'ready'),
            wait(PRELOAD_MAX_WAIT_MS).then(() => 'timeout'),
        ]);

        if (preloadState === 'timeout') {
            isPreloadLagging.value = true;
            await preloadPromise;
            isPreloadLagging.value = false;
        }

        isPreparingSlide.value = false;

        applyCurrentSlide(targetIndex);

        if (desktopContainerRef.value) {
            desktopContainerRef.value.classList.remove(desktopOutClass);
            desktopContainerRef.value.classList.add(desktopInClass);
        }
        if (mobileContainerRef.value) {
            mobileContainerRef.value.classList.remove('transitioning-out');
            mobileContainerRef.value.classList.add('transitioning-in');
        }

        setTimeout(() => {
            if (desktopContainerRef.value) {
                desktopContainerRef.value.classList.remove(desktopInClass);
            }
            if (mobileContainerRef.value) {
                mobileContainerRef.value.classList.remove('transitioning-in');
            }

            isTransitioning.value = false;
        }, TRANSITION_MS);
    }, TRANSITION_MS);
};

const goToNextSlide = () => {
    runSlideTransition(currentSlideIndex.value + 1, 'next');
};

const goToPrevSlide = () => {
    runSlideTransition(currentSlideIndex.value - 1, 'prev');
};

const calculateScrollOffset = () => {
    nextTick(() => {
        if (slideContentRef.value && scrollImageRef.value) {
            const containerHeight = slideContentRef.value.offsetHeight;
            const imageHeight = scrollImageRef.value.offsetHeight;
            
            if (imageHeight > containerHeight) {
                const offset = ((imageHeight - containerHeight) / imageHeight) * 100;
                scrollOffset.value = offset;
            }
        }
    });
};

const calculateScrollOffsetMobile = () => {
    nextTick(() => {
        if (slideContentMobileRef.value && scrollImageMobileRef.value) {
            const containerHeight = slideContentMobileRef.value.offsetHeight;
            const imageHeight = scrollImageMobileRef.value.offsetHeight;
            
            if (imageHeight > containerHeight) {
                const offset = ((imageHeight - containerHeight) / imageHeight) * 100;
                scrollOffsetMobile.value = offset;
            }
        }
    });
};

onMounted(() => {
    applyCurrentSlide(0);
    calculateScrollOffset();
    calculateScrollOffsetMobile();
    
    // Пересчитываем при изменении размера окна
    window.addEventListener('resize', () => {
        calculateScrollOffset();
        calculateScrollOffsetMobile();
    });
});

const handleSliderMouseEnter = () => {
    isSliderHovered.value = true;
    
    // Останавливаем анимацию мобильного элемента
    if (isMobileHovered.value) {
        isMobileHovered.value = false;
        
        if (scrollImageMobileRef.value) {
            const element = scrollImageMobileRef.value;
            
            const matrix = window.getComputedStyle(element).transform;
            const matrixValues = matrix.match(/matrix.*\((.+)\)/)[1].split(', ');
            const currentY = parseFloat(matrixValues[5]) || 0;
            
            const startTime = Date.now();
            const duration = 1000;
            
            const animate = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                
                const newY = currentY + (0 - currentY) * easeProgress;
                element.style.transform = `translateY(${newY}px)`;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.transform = 'translateY(0)';
                }
            };
            
            requestAnimationFrame(animate);
        }
    }
};

const handleSliderMouseLeave = () => {
    isSliderHovered.value = false;
    
    // Плавно возвращаем изображение в исходное положение
    if (scrollImageRef.value) {
        const element = scrollImageRef.value;
        
        // Получаем текущее значение translateY
        const matrix = window.getComputedStyle(element).transform;
        const matrixValues = matrix.match(/matrix.*\((.+)\)/)[1].split(', ');
        const currentY = parseFloat(matrixValues[5]) || 0;
        
        // Анимируем возврат
        const startTime = Date.now();
        const duration = 1000; // 1 секунда
        
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Используем ease-out кривую
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            const newY = currentY + (0 - currentY) * easeProgress;
            element.style.transform = `translateY(${newY}px)`;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.transform = 'translateY(0)';
            }
        };
        
        requestAnimationFrame(animate);
    }
};

const handleMobileMouseEnter = () => {
    isMobileHovered.value = true;
    
    // Останавливаем анимацию десктопного элемента
    if (isSliderHovered.value) {
        isSliderHovered.value = false;
        
        if (scrollImageRef.value) {
            const element = scrollImageRef.value;
            
            const matrix = window.getComputedStyle(element).transform;
            const matrixValues = matrix.match(/matrix.*\((.+)\)/)[1].split(', ');
            const currentY = parseFloat(matrixValues[5]) || 0;
            
            const startTime = Date.now();
            const duration = 1000;
            
            const animate = () => {
                const elapsed = Date.now() - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                
                const newY = currentY + (0 - currentY) * easeProgress;
                element.style.transform = `translateY(${newY}px)`;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.transform = 'translateY(0)';
                }
            };
            
            requestAnimationFrame(animate);
        }
    }
};

const handleMobileMouseLeave = () => {
    isMobileHovered.value = false;
    
    // Плавно возвращаем изображение в исходное положение
    if (scrollImageMobileRef.value) {
        const element = scrollImageMobileRef.value;
        
        // Получаем текущее значение translateY
        const matrix = window.getComputedStyle(element).transform;
        const matrixValues = matrix.match(/matrix.*\((.+)\)/)[1].split(', ');
        const currentY = parseFloat(matrixValues[5]) || 0;
        
        // Анимируем возврат
        const startTime = Date.now();
        const duration = 1000; // 1 секунда
        
        const animate = () => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Используем ease-out кривую
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            const newY = currentY + (0 - currentY) * easeProgress;
            element.style.transform = `translateY(${newY}px)`;
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.style.transform = 'translateY(0)';
            }
        };
        
        requestAnimationFrame(animate);
    }
};
</script>

<template>
    <Head title="Trinity Studio" />

    <div class="min-h-screen bg-gray-100 font-sans text-gray-900 tracking-tight">
        <header class="pt-8 bg-[#ffffff]">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-3 sm:px-5 lg:px-5">
                <Link :href="'/'" class="">
                    <img src="/images/logotype1.svg" alt="Logo" />
                </Link>

                <nav class="hidden items-center gap-5 text-[11px] font-medium text-gray-600 sm:flex">
                    <a href="#projects" class="hover:text-gray-900 text-[#333333] text-[16px]">Проекты</a>
                    <a href="#services" class="hover:text-gray-900 text-[#333333] text-[16px]">Услуги</a>
                    <a href="#about" class="hover:text-gray-900 text-[#333333] text-[16px]">О нас</a>
                </nav>

                
                    <a href="#contacts" class="rounded-full bg-[#333333] flex items-center font-normal pl-[25px] pr-[15px] py-[6px] text-[16px] text-white hover:bg-gray-700 gap-3">
                        <span class="block">Контакт</span>
                        <span class="icon w-[9px] h-[10px] block  min-w-[14px]">
                            <svg class="h-full w-full block"><use xlink:href="/images/sprite.svg#arrow"></use></svg>
                        </span>
                    </a>
                    
            
            </div>
        </header>

        <main class="bg-[#ffffff]">
            <section class="mx-auto max-w-6xl px-4 pb-28 pt-[128px] sm:px-6 lg:px-5">
                <div class="text-center">
                    <h1 class="text-[#333333] text-4xl font-bold leading-[1] tracking-[-0.03em] sm:text-6xl md:text-[92px]">Создаём сайты,<br />которые работают</h1>
                    <p class="mx-auto mt-8  text-[12px] leading-relaxed text-[#333333] sm:text-[18px]" id="about">
                        Помогаем клиентам выйти в интернет: сайт, дизайн, реклама — легко и эффективно.
                    </p>
                </div>
                    <div class="relative slider group mt-[120px]" :class="{ 'is-hovered': isSliderHovered }" :style="{ '--scroll-offset': scrollOffset + '%' }">
                        <div ref="desktopContainerRef" class="desktop relative" @mouseenter="handleSliderMouseEnter" @mouseleave="handleSliderMouseLeave">
                            <div ref="slideContentRef" class="slide-content max-w-[864px] h-[542px] overflow-hidden absolute top-[37px] left-[124px]">
                                <Link :href="currentSlideLink" class="slide"><img ref="scrollImageRef" :src="currentDesktopImage" alt="" class="scroll-image" @load="calculateScrollOffset"></Link>
                            </div>
                            <div class="mockup">
                                <img src="/images/nout_mockup.svg" alt="">
                            </div>
                            <Link :href="currentSlideLink" class="pointer slide-glint max-w-[864px] rounded-tr-[24px] w-[65%] h-[611px] overflow-hidden  absolute top-0 left-auto right-[102px] bg-[rgba(255,255,255,0.2)] z-999" style="clip-path: polygon(80% 0, 100% 0, 100% 100%, 0 100%)">
                                
                            </Link>
                        </div>
                        <div ref="mobileContainerRef" class="mobile cursor-pointer absolute top-[133px] right-14 max-w-[308px] w-full h-[620px] flex items-center justify-center" :class="{ 'is-hovered': isMobileHovered }" :style="{ '--scroll-offset-mobile': scrollOffsetMobile + '%' }" @mouseenter="handleMobileMouseEnter" @mouseleave="handleMobileMouseLeave">
                            <div ref="slideContentMobileRef" class="slide-content--mobile overflow-hidden absolute top-[1px] w-[91%] h-[99%] rounded-[50px]">
                                <Link :href="currentSlideLink" class="slide-mobile"><img ref="scrollImageMobileRef" :src="currentMobileImage" alt="" class="scroll-image-mobile" @load="calculateScrollOffsetMobile"></Link>
                            </div>
                            <div class="mockup-mobile absolute">
                                <img src="/images/smartphone.svg" alt="">
                            </div>
                        </div>
                        <div class="arrows absolute z-50 top-1/2 left-0 w-full flex items-center justify-between h-0">
                            <div class="arrow-prev h-8 w-8 min-w-8 rounded-full bg-[rgba(0,0,0,0.5)] hover:bg-[#333333] flex items-center justify-center cursor-pointer" @click="goToPrevSlide">
                                <span class="icon w-[9px] h-[10px] block  min-w-[14px] rotate-180">
                                    <svg class="h-full w-full block fill-[#ffffff]"><use xlink:href="/images/sprite.svg#slide-arrow"></use></svg>
                                </span>
                            </div>
                            <div class="arrow-next h-8 w-8 min-w-8 rounded-full bg-[rgba(0,0,0,0.5)] hover:bg-[#333333] flex items-center justify-center cursor-pointer" @click="goToNextSlide">
                                <span class="icon w-[9px] h-[10px] block  min-w-[14px]">
                                    <svg class="h-full w-full block fill-[#ffffff]"><use xlink:href="/images/sprite.svg#slide-arrow"></use></svg>
                                </span>
                            </div>
                        </div>
                        <div v-if="isPreloadLagging" class="preload-indicator" aria-hidden="true" />
                    </div>
                
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8 pt-[180px]" id="services">
                <div class="">
                    <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-5xl mb-[30px] text-[#333333]">Что мы делаем?</h2>
                    <p class="max-w-[595px] text-[16px] leading-relaxed text-[#333333] sm:justify-self-end">
                        Мы создаём цифровые решения, которые работают на ваш бизнес: от дизайна и разработки до сопровождения,
                        продвижения и рекламы. <b>Наша цель — не просто красивый интерфейс,</b> а реальный результат: рост трафика, клиентов и доверия к бренду.
                    </p>
                </div>

                <div class="mt-10 flex items-center gap-5 text-2xl font-semibold sm:text-[30px] tabs tabs--main">
                    <span
                        class="tab cursor-pointer"
                        :class="activeTab === 'services' ? 'tab--active text-[#333333]' : 'text-gray-400'
                        "
                        @click="switchTab('services')"
                    >
                        Услуги
                    </span>
                    <span
                        class="tab cursor-pointer"
                        :class="activeTab === 'faq' ? 'tab--active text-[#333333]' : 'text-gray-400'
                        "
                        @click="switchTab('faq')"
                    >
                        FAQ
                    </span>
                </div>

                <!-- services content -->
                <div
                    ref="servicesContainerRef"
                    class="flex flex-wrap tab-content mt-[50px] gap-[30px] services-grid relative"
                    :class="{ 'tab-content--active': activeTab === 'services' }"
                    @mousemove="handleServicesMouseMove"
                    @mouseenter="handleServicesMouseEnter"
                    @mouseleave="handleServicesMouseLeave"
                >
                    <!-- spotlight effect with transition -->
                    <transition name="spotlight">
                        <div
                            v-if="showSpotlight"
                            class="services-spotlight"
                            :style="{
                                left: spotlightX + 'px',
                                top: spotlightY + 'px'
                            }"
                        ></div>
                    </transition>


                    <Link
                        v-for="service in services"
                        :key="service.title"
                        class="service-card rounded-lg bg-[#262626] p-[30px] text-white flex flex-col justify-between basis-[calc(33%-17px)] min-h-[212px] transition-opacity duration-200"
                        :class="{ 'opacity-50': hoveredService && hoveredService !== service.title }"
                        @mouseenter="hoveredService = service.title"
                        @mouseleave="hoveredService = null"
                    >
                        <h3 class="text-lg font-semibold leading-tight sm:text-[24px]">{{ service.title }}</h3>
                        <p class="text-[12px] font-[400] leading-relaxed text-white sm:text-[16px]">{{ service.text }}</p>
                    </Link>
                </div>

                <!-- FAQ content -->
                <div
                    class="mt-8 tab-content faq-grid w-full"
                    :class="{ 'tab-content--active': activeTab === 'faq' }"
                    v-if="activeTab === 'faq'"
                >
                    <div class="space-y-4 w-full">
                        <div
                            v-for="(item, index) in faqs"
                            :key="index"
                            class="rounded-lg py-5 px-9 mt-1!important bg-[#f7f4f4] faq-item"
                        >
                            <button
                                class="w-full text-left font-semibold flex justify-between items-center"
                                @click="toggleFaq(index)"
                            >
                                <span class="text-[22px]">{{ item.question }}</span>
                                <span class="ml-2">
                                    <svg v-if="!item.open" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </span>
                            </button>
                            <div v-show="item.open" class="mt-2 text-sm text-gray-700" v-html="item.answer"></div>
                        </div>
                    </div>
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

<style scoped>
.scroll-image {
    will-change: transform;
}

.slider.group:not(.is-hovered) .scroll-image {
    animation: none !important;
}

.slider.group.is-hovered .scroll-image {
    animation: realisticScroll 11s infinite;
}

.scroll-image-mobile {
    will-change: transform;
}

.mobile:not(.is-hovered) .scroll-image-mobile {
    animation: none !important;
}

.mobile.is-hovered .scroll-image-mobile {
    animation: realisticScrollMobile 11s infinite;
}

.arrows {
    opacity: 0;
    transition: opacity 0.3s ease-in-out;
    pointer-events: none;
}

.slider:hover .arrows {
    opacity: 1;
    pointer-events: auto;
}

.preload-indicator {
    position: absolute;
    top: 52%;
    left: 50%;
    z-index: 60;
    width: 18px;
    height: 18px;
    margin-left: -9px;
    margin-top: -9px;
    border-radius: 9999px;
    border: 2px solid rgba(51, 51, 51, 0.2);
    border-top-color: rgba(51, 51, 51, 0.9);
    animation: preloadSpin 0.7s linear infinite;
}

/* Анимации переключения слайдов */

/* Desktop mockup - выезд вправо (next) */
.desktop.transitioning-out-next {
    animation: slideOutRight 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

/* Desktop mockup - въезд слева (next) */
.desktop.transitioning-in-next {
    animation: slideInLeft 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

/* Desktop mockup - выезд влево (prev) */
.desktop.transitioning-out-prev {
    animation: slideOutLeft 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

/* Desktop mockup - въезд справа (prev) */
.desktop.transitioning-in-prev {
    animation: slideInRight 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

/* Mobile mockup - улетает в экран */
.mobile.transitioning-out {
    animation: flyIntoScreen 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

/* Mobile mockup - вылетает из экрана */
.mobile.transitioning-in {
    animation: flyOutOfScreen 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
}

@keyframes slideOutRight {
    0% {
        transform: translateX(0);
        opacity: 1;
    }
    100% {
        transform: translateX(100%);
        opacity: 0;
    }
}

@keyframes slideInLeft {
    0% {
        transform: translateX(-100%);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutLeft {
    0% {
        transform: translateX(0);
        opacity: 1;
    }
    100% {
        transform: translateX(-100%);
        opacity: 0;
    }
}

@keyframes slideInRight {
    0% {
        transform: translateX(100%);
        opacity: 0;
    }
    100% {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes flyIntoScreen {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    100% {
        transform: scale(0.3);
        opacity: 0;
    }
}

@keyframes flyOutOfScreen {
    0% {
        transform: scale(1.5);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes preloadSpin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}

@keyframes realisticScroll {
    0% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0.45, 0.05, 0.55, 0.95);
    }
    3% {
        transform: translateY(calc(var(--scroll-offset) * -0.02));
    }
    10% {
        transform: translateY(calc(var(--scroll-offset) * -0.08));
    }
    12% {
        transform: translateY(calc(var(--scroll-offset) * -0.10));
    }
    15% {
        transform: translateY(calc(var(--scroll-offset) * -0.11));
    }
    21% {
        transform: translateY(calc(var(--scroll-offset) * -0.20));
    }
    28% {
        transform: translateY(calc(var(--scroll-offset) * -0.32));
    }
    31% {
        transform: translateY(calc(var(--scroll-offset) * -0.35));
    }
    34% {
        transform: translateY(calc(var(--scroll-offset) * -0.37));
    }
    37% {
        transform: translateY(calc(var(--scroll-offset) * -0.40));
    }
    41% {
        transform: translateY(calc(var(--scroll-offset) * -0.45));
    }
    49% {
        transform: translateY(calc(var(--scroll-offset) * -0.60));
        animation-timing-function: linear;
    }
    54% {
        transform: translateY(calc(var(--scroll-offset) * -0.72));
    }
    58% {
        transform: translateY(calc(var(--scroll-offset) * -0.80));
    }
    60% {
        transform: translateY(calc(var(--scroll-offset) * -0.85));
    }
    64% {
        transform: translateY(calc(var(--scroll-offset) * -0.92));
    }
    90% {
        transform: translateY(calc(var(--scroll-offset) * -1));
        animation-timing-function: ease-in;
    }
    /* Быстрый возврат за 1 секунду с ускорением в начале и конце */
    100% {
        transform: translateY(0);
        animation-timing-function: ease-out;
    }
}

@keyframes realisticScrollMobile {
    0% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0.45, 0.05, 0.55, 0.95);
    }
    3% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.02));
    }
    10% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.08));
    }
    12% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.10));
    }
    15% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.11));
    }
    21% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.20));
    }
    28% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.32));
    }
    31% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.35));
    }
    34% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.37));
    }
    37% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.40));
    }
    41% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.45));
    }
    49% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.60));
        animation-timing-function: linear;
    }
    54% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.72));
    }
    58% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.80));
    }
    60% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.85));
    }
    64% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -0.92));
    }
    90% {
        transform: translateY(calc(var(--scroll-offset-mobile) * -1));
        animation-timing-function: ease-in;
    }
    /* Быстрый возврат за 1 секунду с ускорением в начале и конце */
    100% {
        transform: translateY(0);
        animation-timing-function: ease-out;
    }
}

/* hide inactive tab contents */
.tab-content {
    display: none;
}
.tab-content--active {
    display: flex;
}


/* spotlight effect */
.services-grid, .services-grid * {
    cursor: none !important;
}
.services-grid {
    position: relative;
}

.services-spotlight {
    position: absolute;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e5e7eb;
    mix-blend-mode: lighten;
    /* opacity: 0.6; */
    pointer-events: none;
    transform: translate(-50%, -50%);
    z-index: 20; /* below text z-index 30 */
    transition: width 0.2s ease, height 0.2s ease;

}

/* transition classes for spotlight */
.spotlight-enter-active, .spotlight-leave-active {
    /* transition: opacity 0.2s ease; */
        transition: width 0.2s ease, height 0.2s ease;
}
.spotlight-enter-from, .spotlight-leave-to {
    /* opacity: 0; */
    width: 0;
    height: 0;
}
.spotlight-enter-to, .spotlight-leave-from {
    opacity: 1;
}

.services-grid:not(:hover) .services-spotlight {
    /* opacity: 0; */
    width: 0;
    height: 0;
}

/* service card base */
.service-card {
    position: relative;
}


.service-card h3,
.service-card p {
    color: #ffffff;
    mix-blend-mode: difference;
    position: relative;
    z-index: 30;
    transition: color 0.2s ease;
}
</style>
