<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';

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
const mapContainerRef = ref(null);

const mapPopup = ref({
    visible: false,
    x: 0,
    y: 0,
    landId: null,
    title: '',
    hoverImage: '',
    projectCountLabel: '',
    projects: [],
});

const mapDetailsPopup = ref({
    visible: false,
    title: '',
    projects: [],
});

const TRANSITION_MS = 600;
const PRELOAD_MAX_WAIT_MS = 350;
const HOVER_SCROLL_DURATION_MS = 11000;
const SCROLL_RESET_DURATION_MS = 1000;
const REALISTIC_SCROLL_STOPS = [
    { time: 0, position: 0 },
    { time: 0.05, position: 0, easing: 'hold' },
    { time: 0.12, position: 0.09, easing: 'wheel' },
    { time: 0.18, position: 0.12, easing: 'drift' },
    { time: 0.24, position: 0.12, easing: 'hold' },
    { time: 0.32, position: 0.28, easing: 'wheel' },
    { time: 0.38, position: 0.33, easing: 'drift' },
    { time: 0.44, position: 0.33, easing: 'hold' },
    { time: 0.54, position: 0.56, easing: 'wheel' },
    { time: 0.60, position: 0.61, easing: 'drift' },
    { time: 0.66, position: 0.61, easing: 'hold' },
    { time: 0.76, position: 0.82, easing: 'wheel' },
    { time: 0.82, position: 0.87, easing: 'drift' },
    { time: 0.87, position: 0.87, easing: 'hold' },
    { time: 0.96, position: 1, easing: 'wheel' },
    { time: 1, position: 1, easing: 'hold' },
];

const scrollAnimationState = {
    desktop: {
        frameId: null,
        currentY: 0,
    },
    mobile: {
        frameId: null,
        currentY: 0,
    },
};

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
    mapProjects: {
        type: Array,
        default: () => [],
    },
    mapPopupLocations: {
        type: Object,
        default: () => ({}),
    },
});

const getMapPopupMeta = (landId) => props.mapPopupLocations[landId] || {};

const activeCountry = ref('world');

const mapProjectsAllByLand = props.mapProjects.reduce((acc, project) => {
    if (!project.map_land_id) {
        return acc;
    }

    if (!acc[project.map_land_id]) {
        acc[project.map_land_id] = [];
    }

    acc[project.map_land_id].push(project);
    return acc;
}, {});

const filteredMapProjects = computed(() =>
    props.mapProjects.filter((project) => {
        const region = project.region ?? 'russia';
        return region === activeCountry.value;
    })
);

const filteredProjectsCards = computed(() =>
    props.featuredProjects.filter((project) => {
        const region = project.region ?? 'russia';
        return region === activeCountry.value;
    })
);

const updateProjectCardPreviewShift = (cardElement) => {
    const previewElement = cardElement?.querySelector('.card-preview');
    const imageElement = cardElement?.querySelector('.card-preview-image');

    if (!previewElement || !imageElement) {
        return;
    }

    const overflow = Math.max(imageElement.offsetHeight - previewElement.offsetHeight, 0);
    previewElement.style.setProperty('--card-preview-shift', `-${overflow}px`);
};

const handleProjectCardEnter = (event) => {
    const cardElement = event.currentTarget;
    updateProjectCardPreviewShift(cardElement);
    cardElement.classList.add('is-hovered');
    showProjectCursor.value = true;
    updateProjectCursorPosition(event);
};

const handleProjectCardLeave = (event) => {
    event.currentTarget.classList.remove('is-hovered');
    showProjectCursor.value = false;
};

const handleProjectCardImageLoad = (event) => {
    const cardElement = event.currentTarget.closest('.project-card');
    if (!cardElement) {
        return;
    }

    updateProjectCardPreviewShift(cardElement);
};

const projectsContainerRef = ref(null);
const projectCursorX = ref(0);
const projectCursorY = ref(0);
const showProjectCursor = ref(false);

const updateProjectCursorPosition = (event) => {
    if (!projectsContainerRef.value) {
        return;
    }

    const rect = projectsContainerRef.value.getBoundingClientRect();
    projectCursorX.value = event.clientX - rect.left;
    projectCursorY.value = event.clientY - rect.top;
};

const handleProjectCardMove = (event) => {
    updateProjectCursorPosition(event);
};

const handleProjectsGridLeave = () => {
    showProjectCursor.value = false;
};

const mapProjectsByLand = computed(() =>
    filteredMapProjects.value.reduce((acc, project) => {
        if (!project.map_land_id) {
            return acc;
        }

        if (!acc[project.map_land_id]) {
            acc[project.map_land_id] = [];
        }

        acc[project.map_land_id].push(project);
        return acc;
    }, {})
);

const formatProjectCount = (count) => {
    const absCount = Math.abs(count) % 100;
    const lastDigit = absCount % 10;

    if (absCount > 10 && absCount < 20) {
        return `${count} проектов`;
    }

    if (lastDigit === 1) {
        return `${count} проект`;
    }

    if (lastDigit >= 2 && lastDigit <= 4) {
        return `${count} проекта`;
    }

    return `${count} проектов`;
};

const showMapPopup = (landId, event) => {
    cancelHideMapPopup();

    if (mapDetailsPopup.value.visible) {
        return;
    }

    const projects = mapProjectsByLand.value[landId] || [];
    if (!projects.length || !mapContainerRef.value) {
        mapPopup.value.visible = false;
        return;
    }

    const popupMeta = getMapPopupMeta(landId);

    const rect = mapContainerRef.value.getBoundingClientRect();

    mapPopup.value = {
        visible: true,
        x: event.clientX - rect.left + 4,
        y: event.clientY - rect.top + 4,
        landId,
        title: popupMeta.title || '',
        hoverImage: popupMeta.hover_image || '',
        projectCountLabel: formatProjectCount(projects.length),
        projects,
    };
};

const moveMapPopup = (event) => {
    if (!mapPopup.value.visible || !mapContainerRef.value) {
        return;
    }

    const rect = mapContainerRef.value.getBoundingClientRect();
    mapPopup.value.x = event.clientX - rect.left + 4;
    mapPopup.value.y = event.clientY - rect.top + 4;
};

const hideMapPopup = () => {
    mapPopup.value.visible = false;
};

const openMapDetailsPopup = (landId = mapPopup.value.landId) => {
    if (!landId) {
        return;
    }

    const projects = mapProjectsByLand.value[landId] || [];
    if (!projects.length) {
        return;
    }

    const popupMeta = getMapPopupMeta(landId);

    cancelHideMapPopup();
    hideMapPopup();

    mapDetailsPopup.value = {
        visible: true,
        title: popupMeta.title || '',
        projects,
    };
};

const closeMapDetailsPopup = () => {
    mapDetailsPopup.value.visible = false;
};

watch(activeCountry, () => {
    cancelHideMapPopup();
    hideMapPopup();
    closeMapDetailsPopup();
});

const handleResize = () => {
    calculateScrollOffset();
    calculateScrollOffsetMobile();
};

let cleanupMapListeners = () => {};
let mapPopupHideTimer = null;

const cancelHideMapPopup = () => {
    if (mapPopupHideTimer) {
        clearTimeout(mapPopupHideTimer);
        mapPopupHideTimer = null;
    }
};

const scheduleHideMapPopup = () => {
    cancelHideMapPopup();
    mapPopupHideTimer = setTimeout(() => {
        hideMapPopup();
    }, 120);
};

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
        answer: `<p>Эффективный дизайн строится на принципах UX-ориентированности:</p>
<ul class="list-disc list-inside">
    <li>Чёткая визуальная иерархия и понятная навигация</li>
    <li>Адаптивность под все устройства (mobile-first)</li>
    <li>Быстрая загрузка контента (оптимизированные изображения, минимизация скриптов)</li>
    <li>Призывы к действию (CTA), выделенные контрастом и расположением</li>
    <li>Тестирование гипотез через A/B-тесты и тепловые карты</li>
</ul>
<p>Результат: снижение показателя отказов и рост конверсии на 20–40%.</p>`,
        open: false,
        height: 0,
        translateY: '0',
        prevHeight: 0,
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
        height: 0,
        translateY: '0',
        prevHeight: 0,
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
        height: 0,
        translateY: '0',
        prevHeight: 0,
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
        height: 0,
        translateY: '0',
        prevHeight: 0,
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
        height: 0,
        translateY: '0',
        prevHeight: 0,
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
        height: 0,
        translateY: '0',
        prevHeight: 0,
    },
]);

const toggleFaq = (index) => {
    const item = faqs.value[index];
    if (item.open) {
        // closing
        item.height = item.prevHeight;
        item.translateY = item.prevHeight + 'px';
        item.open = false;
        setTimeout(() => {
            item.height = 0;
            item.translateY = '0px';
        }, 0);
    } else {
        // opening: first close all others
        faqs.value.forEach((faq, i) => {
            if (i !== index && faq.open) {
                faq.open = false;
                faq.height = 0;
                faq.translateY = '0px';
            }
        });
        // then open this one
        item.open = true;
        nextTick(() => {
            setTimeout(() => {
                const el = document.querySelector(`.faq-answer-${index}`);
                if (el) {
                    const h = el.scrollHeight;
                    item.translateY = h + 'px';
                    item.height = 0;
                    item.prevHeight = h;
                    setTimeout(() => {
                        item.height = h;
                        item.translateY = '0px';
                    }, 0);
                }
            }, 0);
        });
    }
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

const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

const easeOutPower = (progress, power) => 1 - Math.pow(1 - progress, power);

const getSegmentEasedProgress = (segmentEasing, progress) => {
    switch (segmentEasing) {
        case 'hold':
            return 1;
        case 'wheel':
            return easeOutPower(progress, 2.6);
        case 'drift':
            return easeOutPower(progress, 1.45);
        default:
            return progress;
    }
};

const getSegmentRawProgress = (segmentEasing, easedProgress) => {
    const clampedProgress = clamp(easedProgress, 0, 1);

    switch (segmentEasing) {
        case 'hold':
            return 1;
        case 'wheel':
            return 1 - Math.pow(1 - clampedProgress, 1 / 2.6);
        case 'drift':
            return 1 - Math.pow(1 - clampedProgress, 1 / 1.45);
        default:
            return clampedProgress;
    }
};

const getScrollElements = (target) => {
    if (target === 'desktop') {
        return {
            image: scrollImageRef.value,
            container: slideContentRef.value,
        };
    }

    return {
        image: scrollImageMobileRef.value,
        container: slideContentMobileRef.value,
    };
};

const getMaxScrollTranslateY = (target) => {
    const { image, container } = getScrollElements(target);

    if (!image || !container) {
        return 0;
    }

    const overflow = Math.max(image.offsetHeight - container.offsetHeight, 0);
    return -overflow;
};

const setScrollTranslateY = (target, element, nextY) => {
    scrollAnimationState[target].currentY = nextY;

    if (element) {
        element.style.transform = `translateY(${nextY}px)`;
    }
};

const getDistanceRatioForTimelineProgress = (timelineProgress) => {
    const clampedProgress = clamp(timelineProgress, 0, 1);

    for (let index = 1; index < REALISTIC_SCROLL_STOPS.length; index += 1) {
        const previousStop = REALISTIC_SCROLL_STOPS[index - 1];
        const nextStop = REALISTIC_SCROLL_STOPS[index];

        if (clampedProgress > nextStop.time) {
            continue;
        }

        const segmentDuration = nextStop.time - previousStop.time;
        if (segmentDuration <= 0) {
            return nextStop.position;
        }

        const segmentProgress = (clampedProgress - previousStop.time) / segmentDuration;
        const easedSegmentProgress = getSegmentEasedProgress(nextStop.easing, segmentProgress);
        return previousStop.position + (nextStop.position - previousStop.position) * easedSegmentProgress;
    }

    return 1;
};

const getTimelineProgressForDistanceRatio = (distanceRatio) => {
    const clampedRatio = clamp(distanceRatio, 0, 1);

    for (let index = 1; index < REALISTIC_SCROLL_STOPS.length; index += 1) {
        const previousStop = REALISTIC_SCROLL_STOPS[index - 1];
        const nextStop = REALISTIC_SCROLL_STOPS[index];

        if (clampedRatio > nextStop.position) {
            continue;
        }

        const segmentDistance = nextStop.position - previousStop.position;
        if (segmentDistance <= 0) {
            return nextStop.time;
        }

        const segmentProgress = (clampedRatio - previousStop.position) / segmentDistance;
        const rawSegmentProgress = getSegmentRawProgress(nextStop.easing, segmentProgress);
        return previousStop.time + (nextStop.time - previousStop.time) * rawSegmentProgress;
    }

    return 1;
};

const stopScrollAnimation = (target) => {
    const frameId = scrollAnimationState[target].frameId;

    if (frameId !== null) {
        cancelAnimationFrame(frameId);
    }

    scrollAnimationState[target].frameId = null;
};

const animateScrollImage = ({ element, target, startY, endY, duration, easing }) => {
    if (!element) {
        return;
    }

    stopScrollAnimation(target);

    if (Math.abs(endY - startY) < 0.5) {
        setScrollTranslateY(target, element, endY);
        return;
    }

    const startTime = performance.now();

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const easedProgress = easing(progress);
        const nextY = startY + (endY - startY) * easedProgress;

        setScrollTranslateY(target, element, nextY);

        if (progress < 1) {
            scrollAnimationState[target].frameId = requestAnimationFrame(animate);
            return;
        }

        setScrollTranslateY(target, element, endY);
        scrollAnimationState[target].frameId = null;
    };

    scrollAnimationState[target].frameId = requestAnimationFrame(animate);
};

const animateImageBackToStart = (element, target, startY) => {
    animateScrollImage({
        element,
        target,
        startY,
        endY: 0,
        duration: SCROLL_RESET_DURATION_MS,
        easing: (progress) => 1 - Math.pow(1 - progress, 3),
    });
};

const animateImageOnHover = (element, target, startY) => {
    const maxTranslateY = getMaxScrollTranslateY(target);

    if (Math.abs(maxTranslateY) < 0.5) {
        setScrollTranslateY(target, element, 0);
        return;
    }

    const startDistanceRatio = clamp(startY / maxTranslateY, 0, 1);
    const startTimelineProgress = getTimelineProgressForDistanceRatio(startDistanceRatio);
    const remainingDuration = Math.max(HOVER_SCROLL_DURATION_MS * (1 - startTimelineProgress), 120);

    stopScrollAnimation(target);

    const startTime = performance.now();

    const animate = (currentTime) => {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / remainingDuration, 1);
        const timelineProgress = startTimelineProgress + (1 - startTimelineProgress) * progress;
        const distanceRatio = getDistanceRatioForTimelineProgress(timelineProgress);
        const nextY = maxTranslateY * distanceRatio;

        setScrollTranslateY(target, element, nextY);

        if (progress < 1) {
            scrollAnimationState[target].frameId = requestAnimationFrame(animate);
            return;
        }

        scrollAnimationState[target].frameId = null;
    };

    scrollAnimationState[target].frameId = requestAnimationFrame(animate);
};

const stopHoverScrollAndReturn = (target) => {
    const isDesktop = target === 'desktop';
    const hoveredState = isDesktop ? isSliderHovered : isMobileHovered;
    const element = isDesktop ? scrollImageRef.value : scrollImageMobileRef.value;

    if (!hoveredState.value) {
        return;
    }

    if (!element) {
        hoveredState.value = false;
        stopScrollAnimation(target);
        return;
    }

    const currentY = scrollAnimationState[target].currentY;
    hoveredState.value = false;
    animateImageBackToStart(element, target, currentY);
};

const startHoverScroll = (target) => {
    const isDesktop = target === 'desktop';
    const hoveredState = isDesktop ? isSliderHovered : isMobileHovered;
    const element = isDesktop ? scrollImageRef.value : scrollImageMobileRef.value;

    if (!element) {
        hoveredState.value = true;
        return;
    }

    const currentY = scrollAnimationState[target].currentY;
    hoveredState.value = true;

    animateImageOnHover(element, target, currentY);
};

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

    stopScrollAnimation('desktop');
    stopScrollAnimation('mobile');
    isSliderHovered.value = false;
    isMobileHovered.value = false;

    isTransitioning.value = true;
    transitionDirection.value = direction;

    const nextSlide = getSlideData(targetIndex);
    const preloadPromise = Promise.all([
        preloadImage(nextSlide.desktop_mockup_image),
        preloadImage(nextSlide.mobile_mockup_image),
    ]);

    // Сбрасываем анимацию скролла перед выездом
    if (scrollImageRef.value) {
        setScrollTranslateY('desktop', scrollImageRef.value, 0);
    }
    if (scrollImageMobileRef.value) {
        setScrollTranslateY('mobile', scrollImageMobileRef.value, 0);
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

    window.addEventListener('resize', handleResize);

    const mapListenersCleanup = [];
    Object.keys(mapProjectsAllByLand).forEach((landId) => {
        const landEl = document.getElementById(landId);
        if (!landEl) {
            return;
        }

        const handleEnter = (event) => showMapPopup(landId, event);
        const handleMove = (event) => moveMapPopup(event);

        landEl.classList.add('land-interactive');
        landEl.addEventListener('mouseenter', handleEnter);
        landEl.addEventListener('mousemove', handleMove);
        landEl.addEventListener('mouseleave', scheduleHideMapPopup);

        mapListenersCleanup.push(() => {
            landEl.removeEventListener('mouseenter', handleEnter);
            landEl.removeEventListener('mousemove', handleMove);
            landEl.removeEventListener('mouseleave', scheduleHideMapPopup);
        });
    });

    cleanupMapListeners = () => {
        mapListenersCleanup.forEach((cleanup) => cleanup());
    };
});

onUnmounted(() => {
    cancelHideMapPopup();
    window.removeEventListener('resize', handleResize);
    cleanupMapListeners();
});

const handleSliderMouseEnter = () => {
    startHoverScroll('desktop');

    // Останавливаем анимацию мобильного элемента
    if (isMobileHovered.value) {
        stopHoverScrollAndReturn('mobile');
    }
};

const handleSliderMouseLeave = () => {
    stopHoverScrollAndReturn('desktop');
};

const handleMobileMouseEnter = () => {
    startHoverScroll('mobile');

    // Останавливаем анимацию десктопного элемента
    if (isSliderHovered.value) {
        stopHoverScrollAndReturn('desktop');
    }
};

const handleMobileMouseLeave = () => {
    stopHoverScrollAndReturn('mobile');
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
                            <svg class="h-full w-full block fill-white"><use xlink:href="/images/sprite.svg#arrow"></use></svg>
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
                    <div class="relative slider group mt-[120px]" :class="{ 'is-hovered': isSliderHovered }">
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
                        <div ref="mobileContainerRef" class="mobile cursor-pointer absolute top-[133px] right-14 max-w-[308px] w-full h-[620px] flex items-center justify-center" :class="{ 'is-hovered': isMobileHovered }" @mouseenter="handleMobileMouseEnter" @mouseleave="handleMobileMouseLeave">
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
                        >
                            <span class="services-spotlight-icon" aria-hidden="true">
                                <svg class="h-full w-full block fill-[#000000]"><use xlink:href="/images/sprite.svg#arrow"></use></svg>
                            </span>
                        </div>
                    </transition>


                    <div
                        v-for="service in services"
                        :key="service.title"
                        class="service-card rounded-lg p-[30px] text-white flex flex-col justify-between basis-[calc(33%-17px)] min-h-[212px] transition-opacity duration-200"
                        :class="{ 'opacity-50': hoveredService && hoveredService !== service.title, 'is-hovered': hoveredService === service.title }"
                        @mouseenter="hoveredService = service.title"
                        @mouseleave="hoveredService = null"
                    >
                        <div class="service-card-surface"></div>
                        <h3 class="text-lg font-semibold leading-tight sm:text-[24px]">{{ service.title }}</h3>
                        <p class="text-[12px] font-[400] leading-relaxed text-white sm:text-[16px]">{{ service.text }}</p>
                    </div>
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
                            class="rounded-lg py-5 px-9  bg-[#f7f4f4] faq-item"
                        >
                            <button
                                class="w-full text-left font-semibold flex justify-between items-center"
                                @click="toggleFaq(index)"
                            >
                                <span class="text-[22px] text-[#333 ]">{{ item.question }}</span>
                                <span class="ml-2 cross" :class="{ open: item.open }">
                                    <span class="line horizontal"></span>
                                    <span class="line vertical"></span>
                                </span>
                            </button>
                            <div :class="`text-sm text-[#333] faq-answer faq-answer-${index}`" :style="{ height: item.height + 'px', overflow: 'hidden', transition: 'height 0.3s ease, transform 0.3s ease, padding 0.3s ease', transform: `translateY(${item.translateY})`, paddingTop: item.open ? '20px' : '0', paddingBottom: item.open ? '30px' : '0' }" v-html="item.answer"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8">
                <div class="mb-[100px]">
                    <h2 class="text-3xl text-[#333333] font-bold leading-tight tracking-[-0.02em] sm:text-5xl">География проектов</h2>
                    <p class="max-w-[480px] text-[16px] leading-relaxed sm:justify-self-end text-[#333333] mt-[30px]">
                        Мы работаем с клиентами по всей России и за её пределами — независимо от вашего местоположения, обеспечиваем качественные решения и надёжную поддержку.
                    </p>
                </div>

                <div ref="mapContainerRef" class="map relative">
                    <svg width="1007" height="473" viewBox="0 0 1007 473" fill="none" xmlns="http://www.w3.org/2000/svg">
<path id="land_1" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M6.7394 297.034V291.402L11.6246 288.58L14.0671 289.985L16.5153 291.402V297.028L11.6246 299.85L6.7394 297.034Z"/>
<path id="land_2" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M0 262.402V256.769L4.8907 253.948L9.7758 256.769V262.402L4.8907 265.218L0 262.402Z"/>
<path id="land_3" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M6.7394 273.945V268.313L11.6246 265.492L16.5153 268.313V273.945L11.6246 276.761L6.7394 273.945Z"/>
<path id="land_4" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M13.4733 285.484V279.858L18.364 277.03L23.2491 279.852V285.49L18.364 288.306L13.4733 285.484Z"/>
<path id="land_5" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20.2128 297.034V291.402L25.1035 288.58L27.546 289.985L29.9886 291.402V297.034L25.1035 299.85L20.2128 297.034Z"/>
<path id="land_6" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M0 239.313V233.681L4.8907 230.865L9.7758 233.681V239.313L4.8907 242.141L0 239.313Z"/>
<path id="land_7" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M6.7394 250.857V245.225L11.6246 242.409L16.5153 245.225V250.857L11.6246 253.673L6.7394 250.857Z"/>
<path id="land_8" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M13.4733 262.396V256.769L18.364 253.948L23.2491 256.769V262.402L18.364 265.218L13.4733 262.396Z"/>
<path id="land_9" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20.2128 273.945V268.313L25.1035 265.492L29.9886 268.313V273.945L25.1035 276.761L20.2128 273.945Z"/>
<path id="land_10" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M26.9522 285.49V279.852L31.8429 277.036L36.728 279.857V285.484L31.8429 288.306L26.9522 285.49Z"/>
<path id="land_11" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 297.034V291.396L38.5767 288.58L43.4674 291.402V297.028L38.5767 299.85L33.6916 297.034Z"/>
<path id="land_12" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M6.7394 227.769V222.143L11.6246 219.321L16.5153 222.143V227.769L11.6246 230.591L6.7394 227.769Z"/>
<path id="land_13" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M13.4733 239.313V233.681L18.364 230.865L23.2491 233.681V239.313L18.364 242.141L13.4733 239.313Z"/>
<path id="land_14" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20.2128 250.857V245.225L25.1035 242.409L27.546 243.814L29.9886 245.225V250.857L25.1035 253.673L20.2128 250.857Z"/>
<path id="land_15" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M26.9522 262.402V256.764L29.4003 255.359L31.8429 253.948L36.728 256.769V262.396L31.8429 265.218L26.9522 262.402Z"/>
<path id="land_16" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 273.945V268.308L38.5767 265.492L43.4674 268.313V273.945L38.5767 276.761L33.6916 273.945Z"/>
<path id="land_17" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M40.4254 285.484V279.857L45.3161 277.036L50.2013 279.857V285.484L45.3161 288.306L40.4254 285.484Z"/>
<path id="land_18" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M47.1648 297.028V291.402L49.613 289.985L52.0555 288.58L54.4981 289.985L56.9406 291.396V297.034L52.0555 299.85L47.1648 297.028Z"/>
<path id="land_19" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M13.4733 216.225V210.599L18.364 207.777L23.2491 210.593V216.225L18.364 219.047L13.4733 216.225Z"/>
<path id="land_20" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20.2128 227.769V222.137L25.1035 219.321L27.546 220.726L29.9886 222.137V227.775L25.1035 230.591L20.2128 227.769Z"/>
<path id="land_21" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M26.9522 239.319V233.681L31.8429 230.865L36.728 233.681V239.313L31.8429 242.135L26.9522 239.319Z"/>
<path id="land_22" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 250.863V245.225L38.5767 242.404L43.4674 245.225V250.857L38.5767 253.673L33.6916 250.863Z"/>
<path id="land_23" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M40.4254 262.396V256.769L45.3161 253.948L50.2013 256.769V262.396L45.3161 265.218L40.4254 262.396Z"/>
<path id="land_24" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M56.3581 286.895L53.9043 285.49V279.852L58.795 277.036L63.6801 279.852V285.49L58.795 288.306L56.3581 286.895Z"/>
<path id="land_25" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 227.775V222.137L38.5767 219.321L43.4674 222.143V227.769L38.5767 230.591L33.6916 227.775Z"/>
<path id="land_26" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M40.4254 239.313V233.681L45.3161 230.865L50.2013 233.681V239.313L45.3161 242.135L40.4254 239.313Z"/>
<path id="land_27" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M47.1648 250.857V245.225L49.613 243.809L52.0555 242.409L54.4981 243.814L56.9406 245.225V250.857L52.0555 253.673L47.1648 250.857Z"/>
<path id="land_28" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M53.9043 262.402V256.764L56.3525 255.359L58.795 253.948L63.6801 256.769V262.402L58.795 265.218L53.9043 262.402Z"/>
<path id="land_29" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 204.686V199.049L38.5767 196.233L43.4674 199.054V204.686L38.5767 207.503L33.6916 204.686Z"/>
<path id="land_30" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M47.1648 227.769V222.143L49.613 220.726L52.0555 219.321L54.4981 220.726L56.9406 222.137V227.775L52.0555 230.591L47.1648 227.769Z"/>
<path id="land_31" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M53.9043 239.319V233.681L58.795 230.865L63.6801 233.681V239.313L58.795 242.141L53.9043 239.319Z"/>
<path id="land_32" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33.6916 181.599V175.961L38.5767 173.145L43.4674 175.966V181.599L38.5767 184.42L33.6916 181.599Z"/>
<path id="land_33" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M40.4254 193.137V187.51L45.3161 184.689L50.2013 187.51V193.137L45.3161 195.958L40.4254 193.137Z"/>
<path id="land_34" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M47.1648 204.686V199.054L52.0555 196.233L56.9406 199.049V204.686L52.0555 207.503L47.1648 204.686Z"/>
<path id="land_35" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M53.9043 216.231V210.593L58.795 207.777L63.6801 210.593V216.225L58.795 219.047L53.9043 216.231Z"/>
<path id="land_36" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M60.6438 227.775V222.137L65.5289 219.321L70.4196 222.143V227.769L65.5289 230.591L60.6438 227.775Z"/>
<path id="land_37" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M67.3776 239.313V233.687L72.2683 230.865L77.1535 233.681V239.313L72.2683 242.141L67.3776 239.313Z"/>
<path id="land_38" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M47.1648 181.598V175.966L52.0555 173.15L56.9406 175.966V181.598L52.0555 184.414L47.1648 181.598Z"/>
<path id="land_39" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M53.9043 193.142V187.505L56.3525 186.099L58.795 184.689L63.6801 187.51V193.137L58.795 195.958L53.9043 193.142Z"/>
<path id="land_40" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M60.6438 204.686V199.054L65.5289 196.233L70.4196 199.054V204.686L65.5289 207.503L60.6438 204.686Z"/>
<path id="land_41" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M67.3776 216.225V210.599L72.2683 207.777L77.1535 210.593V216.225L72.2683 219.047L67.3776 216.225Z"/>
<path id="land_42" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M74.1171 227.769V222.143L79.0078 219.321L81.4503 220.726L83.893 222.137V227.775L79.0078 230.591L74.1171 227.769Z"/>
<path id="land_43" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M60.6438 181.598V175.966L65.5289 173.15L70.4196 175.966V181.598L65.5289 184.42L60.6438 181.598Z"/>
<path id="land_44" class="land land-interactive land-with-objects land-with-objects--midle" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M67.3776 193.137V187.51L72.2683 184.689L77.1535 187.51V193.142L72.2683 195.958L67.3776 193.137Z"/>
<path id="land_45" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M74.1171 204.686V199.054L79.0078 196.233L83.893 199.049V204.686L79.0078 207.503L74.1171 204.686Z"/>
<path id="land_46" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M80.8565 216.231V210.593L85.7472 207.777L90.6323 210.599V216.225L85.7472 219.047L80.8565 216.231Z"/>
<path id="land_47" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M87.5959 227.775V222.137L92.481 219.321L97.3717 222.137V227.775L92.481 230.591L87.5959 227.775Z"/>
<path id="land_48" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M74.1171 181.598V175.966L79.0078 173.15L83.893 175.961V181.598L79.0078 184.414L74.1171 181.598Z"/>
<path id="land_49" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M80.8565 193.142V187.505L83.3046 186.099L85.7472 184.689L90.6323 187.51V193.137L85.7472 195.958L80.8565 193.142Z"/>
<path id="land_50" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M87.5959 204.686V199.049L92.481 196.233L97.3717 199.054V204.686L92.481 207.503L87.5959 204.686Z"/>
<path id="land_51" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M94.3298 216.225V210.599L99.2205 207.777L104.106 210.593V216.225L99.2205 219.047L94.3298 216.225Z"/>
<path id="land_52" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M101.069 227.769V222.143L103.517 220.726L105.96 219.321L108.402 220.726L110.845 222.137V227.775L105.96 230.591L101.069 227.769Z"/>
<path id="land_53" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M80.8565 170.054V164.422L85.7472 161.601L90.6323 164.422V170.054L85.7472 172.87L80.8565 170.054Z"/>
<path id="land_54" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M87.5959 181.599V175.966L92.481 173.145L97.3717 175.966V181.599L92.481 184.42L87.5959 181.599Z"/>
<path id="land_55" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M94.3298 193.137V187.51L96.7835 186.099L99.2205 184.689L104.106 187.51V193.137L99.2205 195.958L94.3298 193.137Z"/>
<path id="land_56" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M101.069 204.681V199.054L105.96 196.233L110.845 199.049V204.686L105.96 207.503L101.069 204.681Z"/>
<path id="land_57" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M107.809 216.225V210.593L110.257 209.188L112.694 207.777L117.584 210.593V216.225L112.694 219.047L107.809 216.225Z"/>
<path id="land_58" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 227.775V222.137L119.433 219.315L124.324 222.143V227.769L119.433 230.591L114.548 227.775Z"/>
<path id="land_59" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 262.396V256.769L137.209 255.359L139.646 253.948L144.537 256.769V262.402L139.646 265.218L134.761 262.396Z"/>
<path id="land_60" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M87.5959 158.516V152.878L92.481 150.062L97.3717 152.878V158.51L92.481 161.332L87.5959 158.516Z"/>
<path id="land_61" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M94.3298 170.049V164.428L99.2205 161.601L104.106 164.422V170.054L99.2205 172.87L94.3298 170.049Z"/>
<path id="land_62" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M101.069 181.593V175.966L105.96 173.15L110.845 175.961V181.598L105.96 184.414L101.069 181.593Z"/>
<path id="land_63" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M107.809 193.142V187.51L110.257 186.099L112.694 184.689L117.584 187.51V193.137L112.694 195.958L107.809 193.142Z"/>
<path id="land_64" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 204.686V199.049L119.433 196.233L124.324 199.054V204.686L119.433 207.503L114.548 204.686Z"/>
<path id="land_65" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 216.225V210.599L126.173 207.777L131.058 210.599V216.225L126.173 219.047L121.282 216.225Z"/>
<path id="land_66" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 227.769V222.143L132.912 219.321L135.355 220.726L137.792 222.137V227.775L132.912 230.591L128.021 227.769Z"/>
<path id="land_67" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 239.313V233.681L139.646 230.865L144.537 233.681V239.313L139.646 242.141L134.761 239.313Z"/>
<path id="land_68" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 262.402V256.769L153.125 253.948L155.562 255.359L158.01 256.764V262.402L153.125 265.218L148.234 262.402Z"/>
<path id="land_69" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M101.069 158.51V152.884L105.96 150.062L110.845 152.878V158.516L108.402 159.927L105.96 161.332L101.069 158.51Z"/>
<path id="land_70" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M107.809 170.054V164.422L112.694 161.601L117.584 164.422V170.054L112.694 172.87L107.809 170.054Z"/>
<path id="land_71" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M45 146.454V140.822L49.8851 138L54.7758 140.822V146.454L49.8851 149.27L45 146.454Z"/>
<path id="land_72" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M58 146.454V140.822L62.8851 138L67.7758 140.822V146.454L62.8851 149.27L58 146.454Z"/>
<path id="land_73" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M52 135.454V129.822L56.8851 127L61.7758 129.822V135.454L56.8851 138.27L52 135.454Z"/>
<path id="land_74" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M39 134.454V128.822L43.8851 126L48.7758 128.822V134.454L43.8851 137.27L39 134.454Z"/>
<path id="land_75" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M46 124.454V118.822L50.8851 116L55.7758 118.822V124.454L50.8851 127.27L46 124.454Z"/>
<path id="land_76" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33 123.454V117.822L37.8851 115L42.7758 117.822V123.454L37.8851 126.27L33 123.454Z"/>
<path id="land_77" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M7 124.454V118.822L11.8851 116L16.7758 118.822V124.454L11.8851 127.27L7 124.454Z"/>
<path id="land_78" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M33 101.454V95.8216L37.8851 93L42.7758 95.8216V101.454L37.8851 104.27L33 101.454Z"/>
<path id="land_79" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M40 112.454V106.822L44.8851 104L49.7758 106.822V112.454L44.8851 115.27L40 112.454Z"/>
<path id="land_80" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M14 134.454V128.822L18.8851 126L23.7758 128.822V134.454L18.8851 137.27L14 134.454Z"/>
<path id="land_81" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20 123.454V117.822L24.8851 115L29.7758 117.822V123.454L24.8851 126.27L20 123.454Z"/>
<path id="land_82" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M26 112.454V106.822L30.8851 104L35.7758 106.822V112.454L30.8851 115.27L26 112.454Z"/>
<path id="land_83" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M20 101.454V95.8216L24.8851 93L29.7758 95.8216V101.454L24.8851 104.27L20 101.454Z"/>
<path id="land_84" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M26 90.4537V84.8216L30.8851 82L35.7758 84.8216V90.4537L30.8851 93.2698L26 90.4537Z"/>
<path id="land_85" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 181.599V175.966L119.433 173.145L124.324 175.966V181.599L119.433 184.42L114.548 181.599Z"/>
<path id="land_86" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 193.137V187.51L126.173 184.689L131.058 187.51V193.137L126.173 195.958L121.282 193.137Z"/>
<path id="land_87" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 204.686V199.054L132.912 196.233L137.792 199.049V204.686L132.912 207.503L128.021 204.686Z"/>
<path id="land_88" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 216.225V210.599L139.646 207.777L144.537 210.593V216.225L139.646 219.047L134.761 216.225Z"/>
<path id="land_89" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 227.769V222.143L146.385 219.321L151.276 222.143V227.769L146.385 230.591L141.5 227.769Z"/>
<path id="land_90" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 239.313V233.681L153.125 230.865L158.01 233.681V239.319L153.125 242.141L148.234 239.313Z"/>
<path id="land_91" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 250.857V245.225L157.422 243.809L159.864 242.404L164.749 245.225V250.857L159.864 253.673L154.973 250.857Z"/>
<path id="land_92" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M87.5959 112.339V106.702L92.481 103.886L97.3717 106.707V112.339L92.481 115.161L87.5959 112.339Z"/>
<path id="land_93" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M94.3298 123.878V118.251L99.2205 115.43L104.106 118.246V123.883L99.2205 126.7L94.3298 123.878Z"/>
<path id="land_94" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M107.809 146.966V141.334L112.694 138.518L117.584 141.334V146.966L112.694 149.788L107.809 146.966Z"/>
<path id="land_95" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 158.516V152.878L119.433 150.062L124.324 152.884V158.51L121.876 159.927L119.433 161.332L114.548 158.516Z"/>
<path id="land_96" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 170.054V164.422L126.173 161.601L131.058 164.422V170.054L126.173 172.87L121.282 170.054Z"/>
<path id="land_97" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 181.598V175.966L132.912 173.15L137.792 175.961V181.598L132.912 184.42L128.021 181.598Z"/>
<path id="land_98" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 193.137V187.51L137.209 186.099L139.646 184.689L144.537 187.51V193.142L139.646 195.958L134.761 193.137Z"/>
<path id="land_99" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 204.686V199.054L146.385 196.233L151.276 199.054V204.686L146.385 207.503L141.5 204.686Z"/>
<path id="land_100" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 216.225V210.593L153.125 207.777L158.01 210.593V216.231L153.125 219.047L148.234 216.225Z"/>
<path id="land_101" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M94.3298 100.795V95.1634L99.2205 92.3418L104.106 95.1634V100.795L99.2205 103.617L94.3298 100.795Z"/>
<path id="land_102" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M101.069 112.334V106.707L105.96 103.891L110.845 106.702V112.34L108.402 113.75L105.96 115.156L101.069 112.334Z"/>
<path id="land_103" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 158.51V152.884L132.912 150.062L137.792 152.878V158.516L135.355 159.927L132.912 161.332L128.021 158.51Z"/>
<path id="land_104" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 170.054V164.422L139.646 161.601L144.537 164.422V170.054L139.646 172.87L134.761 170.054Z"/>
<path id="land_105" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 181.599V175.966L146.385 173.145L151.276 175.966V181.599L146.385 184.42L141.5 181.599Z"/>
<path id="land_106" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 193.142V187.51L153.125 184.689L155.562 186.099L158.01 187.505V193.142L153.125 195.958L148.234 193.142Z"/>
<path id="land_107" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 204.686V199.054L159.864 196.233L164.749 199.054V204.686L159.864 207.503L154.973 204.686Z"/>
<path id="land_108" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 216.225V210.593L166.598 207.777L171.489 210.599V216.225L166.598 219.047L161.713 216.225Z"/>
<path id="land_109" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 227.775V222.137L173.337 219.321L178.228 222.137V227.769L173.337 230.591L168.452 227.775Z"/>
<path id="land_110" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 239.313V233.681L180.077 230.865L184.962 233.681V239.319L180.077 242.141L175.186 239.313Z"/>
<path id="land_111" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 250.857V245.225L186.816 242.409L189.259 243.814L191.701 245.225V250.857L186.816 253.673L181.926 250.857Z"/>
<path id="land_112" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 262.402V256.769L193.55 253.948L198.441 256.769V262.402L193.55 265.218L188.665 262.402Z"/>
<path id="land_113" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M110.257 102.206L107.809 100.795V95.1634L112.694 92.3418L117.584 95.1634V100.795L112.694 103.617L110.257 102.206Z"/>
<path id="land_114" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 112.339V106.702L119.433 103.886L124.324 106.707V112.339L119.433 115.161L114.548 112.339Z"/>
<path id="land_115" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 123.883V118.251L126.173 115.43L131.058 118.251V123.883L126.173 126.7L121.282 123.883Z"/>
<path id="land_116" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 135.422V129.796L132.912 126.974L137.792 129.79V135.428L132.912 138.244L128.021 135.422Z"/>
<path id="land_117" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 158.51V152.884L146.385 150.062L151.276 152.884V158.51L146.385 161.332L141.5 158.51Z"/>
<path id="land_118" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 170.054V164.422L153.125 161.601L158.01 164.422V170.054L153.125 172.876L148.234 170.054Z"/>
<path id="land_119" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 181.599V175.966L159.864 173.145L164.749 175.966V181.599L159.864 184.42L154.973 181.599Z"/>
<path id="land_120" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 193.137V187.51L166.598 184.689L171.489 187.51V193.137L166.598 195.958L161.713 193.137Z"/>
<path id="land_121" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 204.686V199.049L173.337 196.233L178.228 199.054V204.686L173.337 207.503L168.452 204.686Z"/>
<path id="land_122" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 216.225V210.593L180.077 207.777L184.962 210.593V216.231L180.077 219.047L175.186 216.225Z"/>
<path id="land_123" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 227.769V222.143L186.816 219.321L191.701 222.143V227.769L186.816 230.591L181.926 227.769Z"/>
<path id="land_124" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 239.313V233.681L193.55 230.865L198.441 233.681V239.313L193.55 242.141L188.665 239.313Z"/>
<path id="land_125" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M114.548 89.2513V83.6192L119.433 80.7976L124.324 83.6192V89.2513L119.433 92.073L114.548 89.2513Z"/>
<path id="land_126" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 100.795V95.1634L126.173 92.3418L131.058 95.1634V100.795L126.173 103.617L121.282 100.795Z"/>
<path id="land_127" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 112.339V106.707L132.912 103.886L137.792 106.702V112.339L135.355 113.75L132.912 115.155L128.021 112.339Z"/>
<path id="land_128" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 123.883V118.251L139.646 115.43L144.537 118.246V123.883L139.646 126.7L134.761 123.883Z"/>
<path id="land_129" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 135.422V129.796L146.385 126.974L151.276 129.796V135.422L146.385 138.244L141.5 135.422Z"/>
<path id="land_130" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M157.416 159.927L154.973 158.51V152.884L159.864 150.062L164.749 152.878V158.51L159.864 161.332L157.416 159.927Z"/>
<path id="land_131" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 170.054V164.422L166.598 161.601L171.489 164.422V170.049L166.598 172.87L161.713 170.054Z"/>
<path id="land_132" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 181.599V175.966L173.337 173.145L178.228 175.966V181.599L173.337 184.42L168.452 181.599Z"/>
<path id="land_133" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 193.137V187.51L180.077 184.689L184.962 187.505V193.142L180.077 195.958L175.186 193.137Z"/>
<path id="land_134" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 204.686V199.054L186.816 196.233L191.701 199.054V204.686L186.816 207.503L181.926 204.686Z"/>
<path id="land_135" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 216.225V210.593L193.55 207.777L198.441 210.599V216.225L193.55 219.047L188.665 216.225Z"/>
<path id="land_136" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 227.775V222.137L200.29 219.321L205.18 222.143V227.769L200.29 230.591L195.405 227.775Z"/>
<path id="land_137" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 239.313V233.681L207.029 230.865L211.914 233.681V239.319L207.029 242.141L202.138 239.313Z"/>
<path id="land_138" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 262.402V256.769L220.502 253.948L225.393 256.769V262.402L220.502 265.218L215.617 262.402Z"/>
<path id="land_139" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 320.116V314.484L254.194 311.663L259.085 314.484V320.122L254.194 322.932L249.303 320.116Z"/>
<path id="land_140" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M121.282 77.7071V72.0807L126.173 69.2534L131.058 72.0807V77.7071L126.173 80.5288L121.282 77.7071Z"/>
<path id="land_141" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M128.021 89.2512V83.6191L132.912 80.803L137.792 83.6191V89.2512L132.912 92.0728L128.021 89.2512Z"/>
<path id="land_142" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M134.761 100.795V95.1634L139.646 92.3418L144.537 95.1634V100.795L139.646 103.617L134.761 100.795Z"/>
<path id="land_143" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 112.339V106.707L146.385 103.886L151.276 106.707V112.334L146.385 115.161L141.5 112.339Z"/>
<path id="land_144" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 123.883V118.246L153.125 115.43L158.01 118.246V123.883L153.125 126.7L148.234 123.883Z"/>
<path id="land_145" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 146.966V141.334L166.598 138.518L171.489 141.34V146.966L166.598 149.788L161.713 146.966Z"/>
<path id="land_146" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 158.516V152.878L173.337 150.062L178.228 152.878V158.51L173.337 161.332L168.452 158.516Z"/>
<path id="land_147" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 170.054V164.422L180.077 161.601L184.962 164.422V170.054L180.077 172.876L175.186 170.054Z"/>
<path id="land_148" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 181.598V175.966L186.816 173.15L191.701 175.966V181.598L186.816 184.42L181.926 181.598Z"/>
<path id="land_149" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 193.142V187.51L191.113 186.099L193.55 184.689L198.441 187.51V193.137L193.55 195.958L188.665 193.142Z"/>
<path id="land_150" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 204.686V199.054L200.29 196.233L205.18 199.054V204.686L200.29 207.503L195.405 204.686Z"/>
<path id="land_151" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 216.225V210.593L207.029 207.777L211.914 210.593V216.231L207.029 219.047L202.138 216.225Z"/>
<path id="land_152" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 227.769V222.137L213.769 219.321L218.654 222.137V227.775L213.769 230.591L208.878 227.769Z"/>
<path id="land_153" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 239.313V233.681L220.502 230.865L225.393 233.681V239.313L220.502 242.135L215.617 239.313Z"/>
<path id="land_154" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 250.857V245.225L227.242 242.409L229.69 243.814L232.132 245.225V250.857L227.242 253.673L222.357 250.857Z"/>
<path id="land_155" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 262.396V256.769L233.981 253.948L236.418 255.359L238.866 256.764V262.402L233.981 265.218L229.09 262.396Z"/>
<path id="land_156" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 273.945V268.313L240.721 265.492L245.606 268.313V273.945L240.721 276.761L235.83 273.945Z"/>
<path id="land_157" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 320.116V314.484L267.667 311.668L272.558 314.484V320.122L267.667 322.932L262.782 320.116Z"/>
<path id="land_158" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M137.209 79.118L134.761 77.7071V72.0807L139.646 69.2534L144.537 72.0807V77.7127L139.646 80.5288L137.209 79.118Z"/>
<path id="land_159" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 89.2513V83.6192L146.385 80.7976L151.276 83.6192V89.2513L146.385 92.073L141.5 89.2513Z"/>
<path id="land_160" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 100.795V95.1634L153.125 92.3418L158.01 95.1578V100.801L153.125 103.617L148.234 100.795Z"/>
<path id="land_161" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 112.334V106.707L159.864 103.886L164.749 106.707V112.339L159.864 115.161L154.973 112.334Z"/>
<path id="land_162" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 146.966V141.334L180.077 138.518L184.962 141.334V146.966L180.077 149.788L175.186 146.966Z"/>
<path id="land_163" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 158.51V152.884L186.816 150.062L191.701 152.884V158.51L189.253 159.927L186.816 161.332L181.926 158.51Z"/>
<path id="land_164" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 170.054V164.422L193.55 161.601L198.441 164.422V170.054L193.55 172.87L188.665 170.054Z"/>
<path id="land_165" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 181.599V175.966L200.29 173.145L205.18 175.966V181.599L200.29 184.42L195.405 181.599Z"/>
<path id="land_166" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 193.142V187.51L207.029 184.689L211.914 187.505V193.142L207.029 195.958L202.138 193.142Z"/>
<path id="land_167" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 204.686V199.054L213.769 196.233L218.654 199.054V204.686L213.769 207.503L208.878 204.686Z"/>
<path id="land_168" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 216.225V210.593L220.502 207.777L225.393 210.599V216.225L220.502 219.047L215.617 216.225Z"/>
<path id="land_169" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 227.775V222.137L227.242 219.321L232.132 222.137V227.775L227.242 230.591L222.357 227.775Z"/>
<path id="land_170" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 239.313V233.681L233.981 230.865L238.866 233.681V239.319L233.981 242.141L229.09 239.313Z"/>
<path id="land_171" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 250.857V245.225L238.278 243.809L240.721 242.409L243.163 243.814L245.606 245.225V250.857L240.721 253.673L235.83 250.857Z"/>
<path id="land_172" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 262.402V256.769L247.454 253.948L252.345 256.769V262.396L247.454 265.218L242.569 262.402Z"/>
<path id="land_173" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 273.945V268.313L254.194 265.492L259.085 268.313V273.945L254.194 276.761L249.303 273.945Z"/>
<path id="land_174" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 308.572V302.94L274.407 300.118L279.297 302.946V308.572L274.407 311.394L269.522 308.572Z"/>
<path id="land_175" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 320.122V314.484L281.146 311.663L286.031 314.484V320.122L281.146 322.932L276.255 320.122Z"/>
<path id="land_176" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 331.66V326.023L287.886 323.207L292.771 326.028V331.66L287.886 334.482L283.001 331.66Z"/>
<path id="land_177" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 343.205V337.567L294.625 334.751L299.51 337.567V343.205L294.625 346.021L289.734 343.205Z"/>
<path id="land_178" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 354.743V349.111L301.359 346.295L306.249 349.116V354.743L301.359 357.564L296.474 354.743Z"/>
<path id="land_179" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 366.293V360.655L308.098 357.839L312.983 360.66V366.287L308.098 369.109L303.213 366.293Z"/>
<path id="land_180" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 377.837V372.199L314.838 369.383L319.723 372.199V377.837L314.838 380.653L309.952 377.837Z"/>
<path id="land_181" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 389.375V383.749L321.577 380.921L326.462 383.749V389.375L321.572 392.197L316.687 389.375Z"/>
<path id="land_182" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 400.919V395.287L328.311 392.471L333.202 395.287V400.919L328.311 403.741L323.426 400.919Z"/>
<path id="land_183" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 412.463V406.831L335.05 404.01L339.935 406.831V412.463L335.05 415.28L330.16 412.463Z"/>
<path id="land_184" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 424.008V418.37L341.79 415.554L346.675 418.37V424.008L341.79 426.824L336.899 424.008Z"/>
<path id="land_185" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 435.546V429.919L348.529 427.098L353.415 429.919V435.546L348.529 438.367L343.639 435.546Z"/>
<path id="land_186" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M141.5 66.1632V60.5367L146.385 57.7151L151.276 60.5367V66.1632L146.385 68.9848L141.5 66.1632Z"/>
<path id="land_187" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 77.7127V72.0807L153.125 69.2534L158.01 72.0751V77.7127L155.567 79.118L153.125 80.5288L148.234 77.7127Z"/>
<path id="land_188" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 89.2513V83.6192L159.864 80.7976L164.749 83.6192V89.2513L159.864 92.073L154.973 89.2513Z"/>
<path id="land_189" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 135.422V129.796L186.816 126.974L191.701 129.796V135.422L186.816 138.244L181.926 135.422Z"/>
<path id="land_190" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 146.966V141.334L193.55 138.518L198.441 141.334V146.966L193.55 149.788L188.665 146.966Z"/>
<path id="land_191" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 158.51V152.878L200.29 150.062L205.18 152.884V158.51L202.732 159.927L200.29 161.332L195.405 158.51Z"/>
<path id="land_192" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 170.054V164.422L207.029 161.601L211.914 164.422V170.054L207.029 172.87L202.138 170.054Z"/>
<path id="land_193" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 181.598V175.966L213.769 173.15L218.654 175.966V181.598L213.769 184.42L208.878 181.598Z"/>
<path id="land_194" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 193.137V187.51L220.502 184.689L225.393 187.51V193.137L220.502 195.958L215.617 193.137Z"/>
<path id="land_195" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 204.686V199.054L227.242 196.233L232.132 199.054V204.686L227.242 207.503L222.357 204.686Z"/>
<path id="land_196" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 216.225V210.599L233.981 207.777L238.866 210.593V216.231L233.981 219.047L229.09 216.225Z"/>
<path id="land_197" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 227.769V222.143L240.721 219.321L245.606 222.137V227.775L240.721 230.591L235.83 227.769Z"/>
<path id="land_198" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 239.313V233.681L247.454 230.865L252.345 233.687V239.313L247.454 242.141L242.569 239.313Z"/>
<path id="land_199" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 250.857V245.225L251.751 243.809L254.194 242.409L259.085 245.225V250.857L254.194 253.673L249.303 250.857Z"/>
<path id="land_200" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 262.402V256.769L260.933 253.948L263.37 255.359L265.819 256.764V262.402L260.933 265.218L256.048 262.402Z"/>
<path id="land_201" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 273.945V268.313L267.667 265.492L272.558 268.313V273.945L267.667 276.761L262.782 273.945Z"/>
<path id="land_202" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 297.034V291.402L278.704 289.985L281.146 288.58L283.589 289.985L286.031 291.402V297.034L281.146 299.85L276.255 297.034Z"/>
<path id="land_203" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 308.572V302.94L287.886 300.118L292.771 302.94V308.572L287.886 311.394L283.001 308.572Z"/>
<path id="land_204" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 320.122V314.484L294.625 311.663L299.51 314.484V320.122L294.625 322.932L289.734 320.122Z"/>
<path id="land_205" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 331.66V326.028L301.359 323.207L306.249 326.028V331.655L301.359 334.482L296.474 331.66Z"/>
<path id="land_206" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 343.21V337.567L308.098 334.751L312.983 337.567V343.205L308.098 346.021L303.213 343.21Z"/>
<path id="land_207" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 354.748V349.111L314.838 346.295L319.723 349.111V354.748L314.838 357.564L309.952 354.748Z"/>
<path id="land_208" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 366.287V360.66L321.577 357.839L326.462 360.66V366.287L321.572 369.109L316.687 366.287Z"/>
<path id="land_209" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 377.837V372.199L328.311 369.383L333.202 372.199V377.831L328.311 380.653L323.426 377.837Z"/>
<path id="land_210" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 389.375V383.749L335.05 380.921L339.935 383.743V389.375L335.05 392.197L330.16 389.375Z"/>
<path id="land_211" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 400.919V395.287L341.79 392.471L346.675 395.287V400.919L341.79 403.741L336.899 400.919Z"/>
<path id="land_212" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 412.463V406.831L348.529 404.01L353.415 406.831V412.463L348.529 415.28L343.639 412.463Z"/>
<path id="land_213" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 424.008V418.37L355.263 415.554L360.154 418.37V424.008L355.263 426.824L350.378 424.008Z"/>
<path id="land_214" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 435.546V429.919L362.003 427.098L366.893 429.919V435.546L362.003 438.367L357.112 435.546Z"/>
<path id="land_215" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M148.234 54.6247V48.9927L153.125 46.1654L158.01 48.9927V54.6247L153.125 57.4408L148.234 54.6247Z"/>
<path id="land_216" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M154.973 66.1632V60.5367L159.864 57.7151L164.749 60.5367V66.1688L159.864 68.9848L154.973 66.1632Z"/>
<path id="land_217" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 77.7071V72.0807L166.598 69.2534L171.489 72.0807V77.7071L166.598 80.5288L161.713 77.7071Z"/>
<path id="land_218" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 100.795V95.1634L180.077 92.3418L184.962 95.1578V100.801L180.077 103.617L175.186 100.795Z"/>
<path id="land_219" class="land land-interactive land-with-objects land-with-objects--hight" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 123.883V118.246L193.55 115.43L198.441 118.251V123.883L193.55 126.7L188.665 123.883Z"/>
<path id="land_220" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 135.428V129.79L200.29 126.974L205.18 129.796V135.422L200.29 138.244L195.405 135.428Z"/>
<path id="land_221" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 146.966V141.334L207.029 138.518L211.914 141.334V146.966L207.029 149.788L202.138 146.966Z"/>
<path id="land_222" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 158.51V152.878L213.769 150.062L218.654 152.878V158.51L213.769 161.332L208.878 158.51Z"/>
<path id="land_223" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 170.054V164.422L220.502 161.601L225.393 164.422V170.054L220.502 172.87L215.617 170.054Z"/>
<path id="land_224" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 181.599V175.966L227.242 173.145L232.132 175.966V181.599L227.242 184.42L222.357 181.599Z"/>
<path id="land_225" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 193.137V187.51L233.981 184.689L236.418 186.099L238.866 187.505V193.142L233.981 195.958L229.09 193.137Z"/>
<path id="land_226" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 204.686V199.054L240.721 196.233L245.606 199.054V204.686L240.721 207.503L235.83 204.686Z"/>
<path id="land_227" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 216.225V210.593L247.454 207.777L252.345 210.599V216.225L247.454 219.047L242.569 216.225Z"/>
<path id="land_228" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 227.769V222.143L254.194 219.321L259.085 222.143V227.769L254.194 230.591L249.303 227.769Z"/>
<path id="land_229" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 239.313V233.681L260.933 230.865L265.819 233.681V239.319L260.933 242.141L256.048 239.313Z"/>
<path id="land_230" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 250.857V245.225L267.667 242.409L270.115 243.814L272.558 245.225V250.857L267.667 253.673L262.782 250.857Z"/>
<path id="land_231" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 262.402V256.764L274.407 253.948L279.297 256.769V262.396L274.407 265.218L269.522 262.402Z"/>
<path id="land_232" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 273.945V268.313L281.146 265.492L286.031 268.313V273.945L281.146 276.761L276.255 273.945Z"/>
<path id="land_233" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 285.49V279.852L287.886 277.03L292.771 279.852V285.49L287.886 288.306L283.001 285.49Z"/>
<path id="land_234" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 297.034V291.396L294.625 288.58L299.51 291.402V297.034L294.625 299.85L289.734 297.034Z"/>
<path id="land_235" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 308.572V302.94L301.359 300.118L306.249 302.946V308.572L301.359 311.394L296.474 308.572Z"/>
<path id="land_236" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 320.122V314.484L308.098 311.663L312.983 314.484V320.122L308.098 322.932L303.213 320.122Z"/>
<path id="land_237" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 331.66V326.028L314.838 323.207L319.723 326.028V331.66L314.838 334.482L309.952 331.66Z"/>
<path id="land_238" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 343.205V337.573L321.572 334.751L326.462 337.567V343.205L321.572 346.021L316.687 343.205Z"/>
<path id="land_239" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 354.748V349.111L328.311 346.295L333.202 349.116V354.743L328.311 357.564L323.426 354.748Z"/>
<path id="land_240" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 366.287V360.66L335.05 357.839L339.935 360.655V366.293L335.05 369.109L330.16 366.287Z"/>
<path id="land_241" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 377.831V372.199L341.79 369.383L346.675 372.199V377.831L341.79 380.653L336.899 377.831Z"/>
<path id="land_242" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 389.375V383.743L348.529 380.921L353.415 383.749V389.375L348.529 392.197L343.639 389.375Z"/>
<path id="land_243" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 400.919V395.287L355.263 392.471L360.154 395.287V400.919L355.263 403.741L350.378 400.919Z"/>
<path id="land_244" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 412.458V406.831L362.003 404.01L366.893 406.831V412.463L362.003 415.285L357.112 412.458Z"/>
<path id="land_245" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 424.008V418.37L368.742 415.554L373.627 418.37V424.008L368.742 426.824L363.857 424.008Z"/>
<path id="land_246" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 435.546V429.919L375.481 427.098L380.366 429.919V435.546L375.481 438.367L370.591 435.546Z"/>
<path id="land_247" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M161.713 54.6246V48.9925L166.598 46.1709L171.489 48.9925V54.619L166.598 57.4407L161.713 54.6246Z"/>
<path id="land_248" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 66.1688V60.5311L173.337 57.7151L178.228 60.5367V66.1632L173.337 68.9848L168.452 66.1688Z"/>
<path id="land_249" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 77.7071V72.0807L180.077 69.2534L184.962 72.0751V77.7127L180.077 80.5288L175.186 77.7071Z"/>
<path id="land_250" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 89.2512V83.6191L186.816 80.803L191.701 83.6191V89.2512L186.816 92.0728L181.926 89.2512Z"/>
<path id="land_251" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 100.795V95.1634L193.55 92.3418L198.441 95.1634V100.795L193.55 103.617L188.665 100.795Z"/>
<path id="land_252" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 123.883V118.246L207.029 115.43L211.914 118.246V123.883L207.029 126.7L202.138 123.883Z"/>
<path id="land_253" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 135.428V129.796L213.769 126.974L218.654 129.79V135.428L213.769 138.244L208.878 135.428Z"/>
<path id="land_254" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 146.966V141.334L220.502 138.518L225.393 141.334V146.966L220.502 149.788L215.617 146.966Z"/>
<path id="land_255" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 158.51V152.878L227.242 150.062L232.132 152.878V158.51L229.684 159.927L227.242 161.332L222.357 158.51Z"/>
<path id="land_256" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 170.054V164.422L233.981 161.601L238.866 164.422V170.054L233.981 172.87L229.09 170.054Z"/>
<path id="land_257" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 181.599V175.966L240.721 173.145L245.606 175.966V181.599L240.721 184.42L235.83 181.599Z"/>
<path id="land_258" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 193.142V187.51L245.018 186.099L247.454 184.689L252.345 187.51V193.137L247.454 195.958L242.569 193.142Z"/>
<path id="land_259" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 204.686V199.054L254.194 196.233L259.085 199.054V204.686L254.194 207.503L249.303 204.686Z"/>
<path id="land_260" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 216.225V210.593L260.933 207.777L265.819 210.593V216.231L260.933 219.047L256.048 216.225Z"/>
<path id="land_261" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 227.769V222.143L267.667 219.321L272.558 222.143V227.769L267.667 230.591L262.782 227.769Z"/>
<path id="land_262" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 239.313V233.681L274.407 230.865L279.297 233.687V239.313L274.407 242.141L269.522 239.313Z"/>
<path id="land_263" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 250.857V245.225L278.704 243.809L281.146 242.409L283.589 243.814L286.031 245.225V250.857L281.146 253.673L276.255 250.857Z"/>
<path id="land_264" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 262.402V256.764L287.886 253.948L292.771 256.764V262.402L287.886 265.218L283.001 262.402Z"/>
<path id="land_265" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 273.945V268.313L294.625 265.492L299.51 268.313V273.945L294.625 276.761L289.734 273.945Z"/>
<path id="land_266" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 285.49V279.852L301.359 277.036L306.249 279.857V285.484L301.359 288.306L296.474 285.49Z"/>
<path id="land_267" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 297.034V291.396L308.098 288.58L312.983 291.402V297.034L308.098 299.85L303.213 297.034Z"/>
<path id="land_268" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 308.572V302.94L314.838 300.118L319.723 302.94V308.572L314.838 311.394L309.952 308.572Z"/>
<path id="land_269" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 320.116V314.49L321.572 311.663L326.462 314.484V320.116L321.572 322.932L316.687 320.116Z"/>
<path id="land_270" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 331.66V326.028L328.311 323.207L333.202 326.028V331.66L328.311 334.482L323.426 331.66Z"/>
<path id="land_271" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 343.205V337.567L335.05 334.751L339.935 337.567V343.205L335.05 346.021L330.16 343.205Z"/>
<path id="land_272" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 354.743V349.116L341.79 346.295L346.675 349.116V354.743L341.79 357.564L336.899 354.743Z"/>
<path id="land_273" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 366.287V360.66L348.529 357.839L353.415 360.66V366.287L348.529 369.109L343.639 366.287Z"/>
<path id="land_274" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 377.831V372.199L355.263 369.383L360.154 372.199V377.831L355.263 380.653L350.378 377.831Z"/>
<path id="land_275" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 389.375V383.749L362.003 380.921L366.893 383.743V389.375L362.003 392.197L357.112 389.375Z"/>
<path id="land_276" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 400.919V395.287L368.742 392.471L373.627 395.287V400.919L368.742 403.741L363.857 400.919Z"/>
<path id="land_277" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 412.458V406.831L375.481 404.01L380.366 406.831V412.463L375.481 415.28L370.591 412.458Z"/>
<path id="land_278" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 424.008V418.37L382.215 415.554L387.106 418.37V424.008L382.215 426.824L377.33 424.008Z"/>
<path id="land_279" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M168.452 43.0804V37.4483L173.337 34.6267L178.228 37.4483V43.0804L173.337 45.9021L168.452 43.0804Z"/>
<path id="land_280" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M175.186 54.6246V48.9925L180.077 46.1709L184.962 48.9925V54.6246L180.077 57.4407L175.186 54.6246Z"/>
<path id="land_281" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 66.1632V60.5367L186.816 57.7151L191.701 60.5367V66.1632L186.816 68.9848L181.926 66.1632Z"/>
<path id="land_282" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 77.7127V72.0807L193.55 69.2534L198.441 72.0807V77.7071L193.55 80.5288L188.665 77.7127Z"/>
<path id="land_283" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 89.2513V83.6192L200.29 80.7976L205.18 83.6192V89.2513L200.29 92.073L195.405 89.2513Z"/>
<path id="land_284" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 100.795V95.1634L207.029 92.3418L211.914 95.1578V100.801L207.029 103.617L202.138 100.795Z"/>
<path id="land_285" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 112.339V106.707L213.769 103.886L218.654 106.707V112.339L213.769 115.155L208.878 112.339Z"/>
<path id="land_286" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 123.883V118.246L220.502 115.43L225.393 118.251V123.883L220.502 126.7L215.617 123.883Z"/>
<path id="land_287" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 135.428V129.79L227.242 126.974L232.132 129.79V135.428L227.242 138.244L222.357 135.428Z"/>
<path id="land_288" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 146.966V141.334L233.981 138.518L238.866 141.334V146.966L233.981 149.788L229.09 146.966Z"/>
<path id="land_289" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 158.51V152.884L240.721 150.062L245.606 152.878V158.51L243.158 159.927L240.721 161.332L235.83 158.51Z"/>
<path id="land_290" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 170.054V164.422L247.454 161.601L252.345 164.422V170.049L247.454 172.876L242.569 170.054Z"/>
<path id="land_291" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 181.599V175.966L254.194 173.145L259.085 175.966V181.599L254.194 184.42L249.303 181.599Z"/>
<path id="land_292" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 193.142V187.51L260.933 184.689L263.37 186.099L265.819 187.505V193.142L260.933 195.958L256.048 193.142Z"/>
<path id="land_293" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 204.686V199.054L267.667 196.233L272.558 199.054V204.686L267.667 207.503L262.782 204.686Z"/>
<path id="land_294" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 216.231V210.593L274.407 207.777L279.297 210.599V216.225L274.407 219.047L269.522 216.231Z"/>
<path id="land_295" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 227.769V222.143L281.146 219.321L286.031 222.143V227.769L281.146 230.591L276.255 227.769Z"/>
<path id="land_296" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 239.319V233.681L287.886 230.865L292.771 233.681V239.319L287.886 242.141L283.001 239.319Z"/>
<path id="land_297" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 250.857V245.225L294.625 242.404L299.51 245.225V250.857L294.625 253.673L289.734 250.857Z"/>
<path id="land_298" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 262.402V256.769L301.359 253.948L306.249 256.769V262.396L301.359 265.218L296.474 262.402Z"/>
<path id="land_299" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 273.945V268.308L308.098 265.492L312.983 268.313V273.945L308.098 276.761L303.213 273.945Z"/>
<path id="land_300" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 285.49V279.852L314.838 277.036L319.723 279.852V285.49L314.838 288.306L309.952 285.49Z"/>
<path id="land_301" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 297.028V291.402L321.577 288.58L326.462 291.402V297.028L321.577 299.85L316.687 297.028Z"/>
<path id="land_302" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 308.572V302.94L328.311 300.118L333.202 302.94V308.572L328.311 311.394L323.426 308.572Z"/>
<path id="land_303" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 320.122V314.484L335.05 311.663L339.935 314.484V320.122L335.05 322.932L330.16 320.122Z"/>
<path id="land_304" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 331.655V326.028L341.79 323.207L346.675 326.028V331.655L341.79 334.482L336.899 331.655Z"/>
<path id="land_305" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 343.205V337.567L348.529 334.751L353.415 337.567V343.205L348.529 346.021L343.639 343.205Z"/>
<path id="land_306" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 354.743V349.116L355.263 346.295L360.154 349.116V354.743L355.263 357.564L350.378 354.743Z"/>
<path id="land_307" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 366.287V360.66L362.003 357.839L366.893 360.655V366.293L362.003 369.109L357.112 366.287Z"/>
<path id="land_308" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 377.831V372.199L368.742 369.383L373.627 372.199V377.837L368.742 380.653L363.857 377.831Z"/>
<path id="land_309" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 389.375V383.749L375.481 380.921L380.366 383.743V389.375L375.481 392.197L370.591 389.375Z"/>
<path id="land_310" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 400.919V395.287L382.215 392.471L387.106 395.287V400.919L382.215 403.741L377.33 400.919Z"/>
<path id="land_311" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 412.463V406.831L388.955 404.01L393.84 406.831V412.463L388.955 415.285L384.07 412.463Z"/>
<path id="land_312" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 424.008V418.37L395.694 415.554L400.579 418.37V424.008L395.694 426.824L390.804 424.008Z"/>
<path id="land_313" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M181.926 43.0804V37.4483L186.816 34.6267L191.701 37.4483V43.0804L186.816 45.8965L181.926 43.0804Z"/>
<path id="land_314" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M188.665 54.6246V48.9925L193.55 46.1709L198.441 48.9925V54.6246L193.55 57.4407L188.665 54.6246Z"/>
<path id="land_315" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 66.1688V60.5367L200.29 57.7151L205.18 60.5367V66.1632L200.29 68.9848L195.405 66.1688Z"/>
<path id="land_316" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M204.592 79.118L202.138 77.7127V72.0807L207.029 69.2534L211.914 72.0751V77.7127L207.029 80.5288L204.592 79.118Z"/>
<path id="land_317" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 89.2512V83.6191L213.769 80.803L218.654 83.6191V89.2512L213.769 92.0728L208.878 89.2512Z"/>
<path id="land_318" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 100.795V95.1634L220.502 92.3418L225.393 95.1634V100.795L220.502 103.617L215.617 100.795Z"/>
<path id="land_319" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 112.339V106.707L227.242 103.886L232.132 106.707V112.339L227.242 115.161L222.357 112.339Z"/>
<path id="land_320" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 123.883V118.251L233.981 115.43L238.866 118.246V123.883L233.981 126.7L229.09 123.883Z"/>
<path id="land_321" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 135.422V129.796L240.721 126.974L245.606 129.79V135.428L240.721 138.244L235.83 135.422Z"/>
<path id="land_322" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 146.966V141.334L247.454 138.518L252.345 141.34V146.966L247.454 149.788L242.569 146.966Z"/>
<path id="land_323" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 158.51V152.884L254.194 150.062L259.085 152.884V158.51L256.637 159.927L254.194 161.332L249.303 158.51Z"/>
<path id="land_324" class="land land-interactive land-with-objects land-with-objects--midle" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 170.054V164.422L260.933 161.601L265.819 164.422V170.054L260.933 172.876L256.048 170.054Z"/>
<path id="land_325" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 181.598V175.966L267.667 173.15L272.558 175.966V181.598L267.667 184.42L262.782 181.598Z"/>
<path id="land_326" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 193.142V187.505L271.964 186.099L274.407 184.689L279.297 187.51V193.137L274.407 195.958L269.522 193.142Z"/>
<path id="land_327" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 204.686V199.054L281.146 196.233L286.031 199.054V204.686L281.146 207.503L276.255 204.686Z"/>
<path id="land_328" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 216.231V210.593L287.886 207.777L292.771 210.593V216.231L287.886 219.047L283.001 216.231Z"/>
<path id="land_329" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 227.775V222.137L294.625 219.321L299.51 222.137V227.769L294.625 230.591L289.734 227.775Z"/>
<path id="land_330" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 239.313V233.681L301.359 230.865L306.249 233.687V239.313L301.359 242.135L296.474 239.313Z"/>
<path id="land_331" class="land land-interactive land-with-objects land-with-objects--midle" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 250.863V245.225L308.098 242.404L312.983 245.225V250.857L308.098 253.673L303.213 250.863Z"/>
<path id="land_332" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 262.402V256.769L314.838 253.948L319.723 256.769V262.402L314.838 265.218L309.952 262.402Z"/>
<path id="land_333" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 273.945V268.313L321.577 265.492L326.462 268.313V273.945L321.572 276.761L316.687 273.945Z"/>
<path id="land_334" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 285.49V279.852L328.311 277.03L333.202 279.852V285.49L328.311 288.306L323.426 285.49Z"/>
<path id="land_335" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 297.034V291.402L332.608 289.985L335.05 288.58L339.935 291.396V297.034L335.05 299.85L330.16 297.034Z"/>
<path id="land_336" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 308.572V302.94L341.79 300.124L346.675 302.94V308.572L341.79 311.394L336.899 308.572Z"/>
<path id="land_337" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 320.122V314.484L348.529 311.663L353.415 314.484V320.116L348.529 322.932L343.639 320.122Z"/>
<path id="land_338" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 331.655V326.028L355.263 323.207L360.154 326.028V331.655L355.263 334.482L350.378 331.655Z"/>
<path id="land_339" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 343.205V337.573L362.003 334.751L366.893 337.567V343.205L362.003 346.021L357.112 343.205Z"/>
<path id="land_340" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 354.743V349.116L368.742 346.295L373.627 349.111V354.748L368.742 357.564L363.857 354.743Z"/>
<path id="land_341" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 366.287V360.66L375.481 357.839L380.366 360.66V366.287L375.481 369.109L370.591 366.287Z"/>
<path id="land_342" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 377.831V372.199L382.215 369.383L387.106 372.199V377.831L382.215 380.653L377.33 377.831Z"/>
<path id="land_343" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 389.375V383.743L388.955 380.921L393.84 383.743V389.375L388.955 392.197L384.07 389.375Z"/>
<path id="land_344" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 400.919V395.287L395.694 392.471L400.579 395.287V400.919L395.694 403.741L390.804 400.919Z"/>
<path id="land_345" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 412.458V406.831L402.434 404.01L407.319 406.831V412.463L402.434 415.285L397.543 412.458Z"/>
<path id="land_346" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M195.405 43.0804V37.4483L200.29 34.6267L205.18 37.4483V43.0804L200.29 45.8965L195.405 43.0804Z"/>
<path id="land_347" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M202.138 54.6246V48.9925L207.029 46.1709L211.914 48.9925V54.6246L207.029 57.4407L202.138 54.6246Z"/>
<path id="land_348" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M208.878 66.1632V60.5367L213.769 57.7151L218.654 60.5367V66.1688L213.769 68.9848L208.878 66.1632Z"/>
<path id="land_349" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 77.7071V72.0807L220.502 69.2534L225.393 72.0807V77.7071L220.502 80.5288L215.617 77.7071Z"/>
<path id="land_350" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 89.2513V83.6192L227.242 80.7976L232.132 83.6192V89.2513L227.242 92.073L222.357 89.2513Z"/>
<path id="land_351" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 100.795V95.1634L233.981 92.3418L238.866 95.1578V100.801L233.981 103.617L229.09 100.795Z"/>
<path id="land_352" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 112.334V106.707L240.721 103.886L245.606 106.707V112.339L240.721 115.155L235.83 112.334Z"/>
<path id="land_353" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 123.883V118.246L247.454 115.43L252.345 118.251V123.883L247.454 126.7L242.569 123.883Z"/>
<path id="land_354" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 135.422V129.796L254.194 126.974L259.085 129.796V135.422L254.194 138.244L249.303 135.422Z"/>
<path id="land_355" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 146.966V141.334L260.933 138.518L265.819 141.334V146.966L260.933 149.788L256.048 146.966Z"/>
<path id="land_356" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 158.51V152.884L267.667 150.062L272.558 152.884V158.51L270.11 159.927L267.667 161.332L262.782 158.51Z"/>
<path id="land_357" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 170.054V164.422L274.407 161.601L279.297 164.422V170.049L274.407 172.876L269.522 170.054Z"/>
<path id="land_358" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 181.599V175.966L281.146 173.145L286.031 175.966V181.599L281.146 184.42L276.255 181.599Z"/>
<path id="land_359" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 193.142V187.505L285.443 186.099L287.886 184.689L292.771 187.505V193.142L287.886 195.958L283.001 193.142Z"/>
<path id="land_360" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 204.686V199.049L294.625 196.233L299.51 199.054V204.686L294.625 207.503L289.734 204.686Z"/>
<path id="land_361" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 216.225V210.593L301.359 207.777L306.249 210.599V216.225L301.359 219.047L296.474 216.225Z"/>
<path id="land_362" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 227.775V222.137L308.098 219.321L312.983 222.137V227.769L308.098 230.591L303.213 227.775Z"/>
<path id="land_363" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 239.313V233.681L314.838 230.865L319.723 233.681V239.313L314.838 242.141L309.952 239.313Z"/>
<path id="land_364" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 250.857V245.225L321.577 242.409L326.462 245.225V250.857L321.572 253.673L316.687 250.857Z"/>
<path id="land_365" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 262.402V256.769L328.311 253.948L330.748 255.359L333.202 256.769V262.402L328.311 265.218L323.426 262.402Z"/>
<path id="land_366" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 273.945V268.313L335.05 265.492L339.935 268.308V273.945L335.05 276.761L330.16 273.945Z"/>
<path id="land_367" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 285.484V279.857L341.79 277.036L346.675 279.857V285.484L341.79 288.306L336.899 285.484Z"/>
<path id="land_368" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 297.034V291.396L348.529 288.58L353.415 291.402V297.034L348.529 299.85L343.639 297.034Z"/>
<path id="land_369" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 308.572V302.94L355.263 300.124L360.154 302.94V308.572L355.263 311.394L350.378 308.572Z"/>
<path id="land_370" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 320.116V314.484L362.003 311.663L366.893 314.484V320.122L362.003 322.932L357.112 320.116Z"/>
<path id="land_371" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 366.293V360.655L388.955 357.839L393.84 360.66V366.287L388.955 369.109L384.07 366.293Z"/>
<path id="land_372" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 377.831V372.199L395.694 369.383L400.579 372.199V377.831L395.694 380.653L390.804 377.831Z"/>
<path id="land_373" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 389.37V383.749L402.434 380.921L407.319 383.743V389.375L402.434 392.197L397.543 389.37Z"/>
<path id="land_374" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 400.919V395.287L409.167 392.471L414.058 395.287V400.919L409.167 403.741L404.282 400.919Z"/>
<path id="land_375" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M215.617 54.6246V48.9925L220.502 46.1709L225.393 48.9925V54.6246L220.502 57.4407L215.617 54.6246Z"/>
<path id="land_376" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M222.357 66.1688V60.5367L227.242 57.7151L232.132 60.5367V66.1688L227.242 68.9848L222.357 66.1688Z"/>
<path id="land_377" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 77.7071V72.0807L233.981 69.2534L238.866 72.0751V77.7127L236.424 79.118L233.981 80.5288L229.09 77.7071Z"/>
<path id="land_378" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 89.2513V83.6192L240.721 80.7976L245.606 83.6192V89.2513L240.721 92.073L235.83 89.2513Z"/>
<path id="land_379" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 100.795V95.1634L247.454 92.3418L252.345 95.1634V100.795L247.454 103.617L242.569 100.795Z"/>
<path id="land_380" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 112.334V106.707L254.194 103.886L259.085 106.707V112.339L254.194 115.155L249.303 112.334Z"/>
<path id="land_381" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 123.883V118.246L260.933 115.43L265.819 118.246V123.883L260.933 126.7L256.048 123.883Z"/>
<path id="land_382" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 135.422V129.796L267.667 126.974L272.558 129.796V135.422L267.667 138.244L262.782 135.422Z"/>
<path id="land_383" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 146.966V141.334L274.407 138.518L279.297 141.34V146.966L274.407 149.788L269.522 146.966Z"/>
<path id="land_384" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 158.51V152.884L281.146 150.062L286.031 152.884V158.51L283.589 159.927L281.146 161.332L276.255 158.51Z"/>
<path id="land_385" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 170.054V164.422L287.886 161.601L292.771 164.422V170.054L287.886 172.876L283.001 170.054Z"/>
<path id="land_386" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 181.599V175.966L294.625 173.145L299.51 175.966V181.599L294.625 184.42L289.734 181.599Z"/>
<path id="land_387" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 193.142V187.51L301.359 184.689L306.249 187.51V193.137L301.359 195.958L296.474 193.142Z"/>
<path id="land_388" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 204.686V199.049L308.098 196.233L312.983 199.054V204.686L308.098 207.503L303.213 204.686Z"/>
<path id="land_389" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 216.225V210.593L314.838 207.777L319.723 210.593V216.225L314.838 219.047L309.952 216.225Z"/>
<path id="land_390" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 227.769V222.143L321.577 219.321L326.462 222.143V227.769L321.572 230.591L316.687 227.769Z"/>
<path id="land_391" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 239.313V233.681L328.311 230.865L333.202 233.681V239.313L328.311 242.141L323.426 239.313Z"/>
<path id="land_392" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 250.857V245.225L332.608 243.809L335.05 242.404L339.935 245.225V250.857L335.05 253.673L330.16 250.857Z"/>
<path id="land_393" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 262.396V256.769L341.79 253.948L346.675 256.769V262.396L341.79 265.218L336.899 262.396Z"/>
<path id="land_394" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 273.945V268.313L348.529 265.492L353.415 268.313V273.945L348.529 276.761L343.639 273.945Z"/>
<path id="land_395" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 285.484V279.857L355.263 277.036L360.154 279.852V285.49L355.263 288.306L350.378 285.484Z"/>
<path id="land_396" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 297.028V291.402L362.003 288.58L366.893 291.396V297.034L362.003 299.85L357.112 297.028Z"/>
<path id="land_397" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 308.572V302.94L368.742 300.118L373.627 302.94V308.572L368.742 311.394L363.857 308.572Z"/>
<path id="land_398" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 320.116V314.484L375.481 311.663L380.366 314.484V320.122L375.481 322.932L370.591 320.116Z"/>
<path id="land_399" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 331.655V326.028L382.215 323.207L387.106 326.028V331.655L382.215 334.482L377.33 331.655Z"/>
<path id="land_400" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 366.287V360.66L402.434 357.839L407.319 360.66V366.287L402.434 369.109L397.543 366.287Z"/>
<path id="land_401" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 377.831V372.199L409.167 369.383L414.058 372.199V377.831L409.167 380.653L404.282 377.831Z"/>
<path id="land_402" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 389.375V383.743L415.907 380.921L420.792 383.743V389.375L415.907 392.197L411.022 389.375Z"/>
<path id="land_403" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 400.919V395.287L422.646 392.471L427.531 395.287V400.919L422.646 403.741L417.755 400.919Z"/>
<path id="land_404" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M229.09 54.6246V48.9925L233.981 46.1709L238.866 48.9925V54.6246L233.981 57.4407L229.09 54.6246Z"/>
<path id="land_405" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M235.83 66.1632V60.5367L240.721 57.7151L245.606 60.5367V66.1688L240.721 68.9848L235.83 66.1632Z"/>
<path id="land_406" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 89.2513V83.6192L254.194 80.7976L259.085 83.6192V89.2513L254.194 92.073L249.303 89.2513Z"/>
<path id="land_407" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M256.048 100.795V95.1634L260.933 92.3418L265.819 95.1578V100.801L260.933 103.617L256.048 100.795Z"/>
<path id="land_408" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 112.334V106.707L267.667 103.886L272.558 106.707V112.339L267.667 115.155L262.782 112.334Z"/>
<path id="land_409" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 123.883V118.246L274.407 115.43L279.297 118.251V123.883L274.407 126.7L269.522 123.883Z"/>
<path id="land_410" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 135.422V129.796L281.146 126.974L286.031 129.796V135.422L281.146 138.244L276.255 135.422Z"/>
<path id="land_411" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 146.966V141.334L287.886 138.518L292.771 141.334V146.966L287.886 149.788L283.001 146.966Z"/>
<path id="land_412" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 158.516V152.878L294.625 150.062L299.51 152.878V158.51L294.625 161.332L289.734 158.516Z"/>
<path id="land_413" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 170.054V164.422L301.359 161.601L306.249 164.422V170.049L301.359 172.87L296.474 170.054Z"/>
<path id="land_414" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 181.599V175.961L308.098 173.145L312.983 175.966V181.599L308.098 184.42L303.213 181.599Z"/>
<path id="land_415" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 193.142V187.51L314.838 184.689L319.723 187.51V193.142L314.838 195.958L309.952 193.142Z"/>
<path id="land_416" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 204.686V199.054L321.577 196.233L326.462 199.054V204.686L321.572 207.503L316.687 204.686Z"/>
<path id="land_417" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 216.225V210.593L328.311 207.777L333.202 210.593V216.225L328.311 219.047L323.426 216.225Z"/>
<path id="land_418" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 227.769V222.143L332.608 220.726L335.05 219.321L339.935 222.137V227.775L335.05 230.591L330.16 227.769Z"/>
<path id="land_419" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 239.313V233.681L341.79 230.865L346.675 233.681V239.313L341.79 242.135L336.899 239.313Z"/>
<path id="land_420" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 250.857V245.225L348.529 242.404L353.415 245.225V250.857L348.529 253.673L343.639 250.857Z"/>
<path id="land_421" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 262.396V256.769L355.263 253.948L360.154 256.769V262.402L355.263 265.218L350.378 262.396Z"/>
<path id="land_422" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 273.945V268.313L362.003 265.492L366.893 268.313V273.945L362.003 276.761L357.112 273.945Z"/>
<path id="land_423" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 285.49V279.852L368.742 277.036L373.627 279.852V285.49L368.742 288.306L363.857 285.49Z"/>
<path id="land_424" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 297.028V291.402L375.481 288.58L380.366 291.402V297.034L375.481 299.85L370.591 297.028Z"/>
<path id="land_425" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 308.572V302.94L382.215 300.118L387.106 302.94V308.572L382.215 311.394L377.33 308.572Z"/>
<path id="land_426" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 320.122V314.484L388.955 311.663L393.84 314.484V320.122L388.955 322.938L384.07 320.122Z"/>
<path id="land_427" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 331.655V326.028L395.694 323.207L400.579 326.028V331.66L395.694 334.482L390.804 331.655Z"/>
<path id="land_428" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 354.743V349.116L409.167 346.295L414.058 349.116V354.743L409.167 357.564L404.282 354.743Z"/>
<path id="land_429" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 366.293V360.655L415.907 357.839L420.792 360.655V366.293L415.907 369.109L411.022 366.293Z"/>
<path id="land_430" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 377.831V372.199L422.646 369.383L427.531 372.199V377.831L422.646 380.653L417.755 377.831Z"/>
<path id="land_431" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 389.375V383.749L429.38 380.921L434.271 383.749V389.375L429.38 392.197L424.495 389.375Z"/>
<path id="land_432" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M242.569 54.6246V48.9925L247.454 46.1709L252.345 48.9925V54.619L247.454 57.4407L242.569 54.6246Z"/>
<path id="land_433" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M249.303 66.1632V60.5367L254.194 57.7151L259.085 60.5367V66.1632L254.194 68.9848L249.303 66.1632Z"/>
<path id="land_434" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M262.782 89.2512V83.6191L267.667 80.803L272.558 83.6191V89.2512L267.667 92.0728L262.782 89.2512Z"/>
<path id="land_435" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 100.801V95.1578L274.407 92.3418L279.297 95.1634V100.795L274.407 103.617L269.522 100.801Z"/>
<path id="land_436" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 112.339V106.707L281.146 103.886L286.031 106.707V112.339L281.146 115.155L276.255 112.339Z"/>
<path id="land_437" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 123.883V118.246L287.886 115.43L292.771 118.246V123.883L287.886 126.7L283.001 123.883Z"/>
<path id="land_438" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 135.428V129.79L294.625 126.974L299.51 129.796V135.428L294.625 138.244L289.734 135.428Z"/>
<path id="land_439" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 146.966V141.334L301.359 138.518L306.249 141.34V146.966L301.359 149.788L296.474 146.966Z"/>
<path id="land_440" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 158.516V152.878L308.098 150.062L312.983 152.878V158.51L308.098 161.332L303.213 158.516Z"/>
<path id="land_441" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 170.054V164.422L314.838 161.601L319.723 164.422V170.054L314.838 172.87L309.952 170.054Z"/>
<path id="land_442" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 181.599V175.966L321.577 173.145L326.462 175.966V181.599L321.572 184.42L316.687 181.599Z"/>
<path id="land_443" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 193.142V187.51L328.311 184.689L330.748 186.099L333.202 187.51V193.137L328.311 195.958L323.426 193.142Z"/>
<path id="land_444" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 204.686V199.054L335.05 196.233L339.935 199.049V204.686L335.05 207.503L330.16 204.686Z"/>
<path id="land_445" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 216.225V210.599L341.79 207.777L346.675 210.599V216.225L341.79 219.047L336.899 216.225Z"/>
<path id="land_446" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 227.775V222.137L348.529 219.321L353.415 222.143V227.769L348.529 230.591L343.639 227.775Z"/>
<path id="land_447" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 239.313V233.681L355.263 230.865L360.154 233.681V239.313L355.263 242.135L350.378 239.313Z"/>
<path id="land_448" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 250.857V245.225L362.003 242.404L366.893 245.225V250.857L362.003 253.673L357.112 250.857Z"/>
<path id="land_449" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 262.402V256.769L368.742 253.948L373.627 256.769V262.402L368.742 265.218L363.857 262.402Z"/>
<path id="land_450" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 273.945V268.313L375.481 265.492L380.366 268.313V273.945L375.481 276.761L370.591 273.945Z"/>
<path id="land_451" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 285.484V279.858L382.215 277.03L387.106 279.852V285.49L382.215 288.306L377.33 285.484Z"/>
<path id="land_452" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 297.034V291.396L388.955 288.58L393.84 291.402V297.034L388.955 299.85L384.07 297.034Z"/>
<path id="land_453" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 308.572V302.94L395.694 300.118L400.579 302.94V308.572L395.694 311.394L390.804 308.572Z"/>
<path id="land_454" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 320.116V314.49L402.434 311.663L407.319 314.484V320.122L402.434 322.938L397.543 320.116Z"/>
<path id="land_455" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 331.655V326.028L409.167 323.207L414.058 326.028V331.655L409.167 334.482L404.282 331.655Z"/>
<path id="land_456" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 377.831V372.199L436.12 369.383L441.01 372.199V377.831L436.12 380.653L431.234 377.831Z"/>
<path id="land_457" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M269.522 77.7127V72.0751L274.407 69.2534L279.297 72.0807V77.7071L274.407 80.5288L269.522 77.7127Z"/>
<path id="land_458" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 89.2513V83.6192L281.146 80.7976L286.031 83.6192V89.2513L281.146 92.073L276.255 89.2513Z"/>
<path id="land_459" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 100.801V95.1578L287.886 92.3418L292.771 95.1578V100.801L287.886 103.617L283.001 100.801Z"/>
<path id="land_460" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 112.339V106.702L294.625 103.886L299.51 106.707V112.339L294.625 115.161L289.734 112.339Z"/>
<path id="land_461" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 123.883V118.246L301.359 115.43L306.249 118.251V123.883L301.359 126.7L296.474 123.883Z"/>
<path id="land_462" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 135.428V129.79L308.098 126.974L312.983 129.796V135.428L308.098 138.244L303.213 135.428Z"/>
<path id="land_463" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 146.966V141.334L314.838 138.518L319.723 141.334V146.966L314.838 149.788L309.952 146.966Z"/>
<path id="land_464" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 158.51V152.884L321.577 150.062L326.462 152.884V158.51L321.572 161.332L316.687 158.51Z"/>
<path id="land_465" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 170.054V164.422L328.311 161.601L333.202 164.422V170.054L328.311 172.876L323.426 170.054Z"/>
<path id="land_466" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 181.599V175.966L335.05 173.145L339.935 175.966V181.599L335.05 184.42L330.16 181.599Z"/>
<path id="land_467" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 193.137V187.51L341.79 184.689L346.675 187.51V193.137L341.79 195.958L336.899 193.137Z"/>
<path id="land_468" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 204.686V199.054L348.529 196.233L353.415 199.054V204.686L348.529 207.503L343.639 204.686Z"/>
<path id="land_469" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 216.225V210.599L355.263 207.777L360.154 210.599V216.225L355.263 219.047L350.378 216.225Z"/>
<path id="land_470" class="land land-interactive land-with-objects land-with-objects--low" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 227.769V222.143L362.003 219.321L366.893 222.137V227.775L362.003 230.591L357.112 227.769Z"/>
<path id="land_471" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 239.313V233.681L368.742 230.865L373.627 233.681V239.313L368.742 242.141L363.857 239.313Z"/>
<path id="land_472" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 250.857V245.225L375.481 242.404L380.366 245.225V250.857L375.481 253.673L370.591 250.857Z"/>
<path id="land_473" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 262.396V256.769L382.215 253.948L387.106 256.769V262.402L382.215 265.218L377.33 262.396Z"/>
<path id="land_474" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 273.945V268.313L388.955 265.492L393.84 268.313V273.945L388.955 276.761L384.07 273.945Z"/>
<path id="land_475" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 285.484V279.857L395.694 277.036L400.579 279.852V285.49L395.694 288.306L390.804 285.484Z"/>
<path id="land_476" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 297.028V291.402L402.434 288.58L407.319 291.402V297.034L402.434 299.85L397.543 297.028Z"/>
<path id="land_477" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 308.572V302.94L409.167 300.124L414.058 302.94V308.572L409.167 311.394L404.282 308.572Z"/>
<path id="land_478" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 320.122V314.484L415.907 311.663L420.792 314.484V320.122L415.907 322.938L411.022 320.122Z"/>
<path id="land_479" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 331.655V326.028L422.646 323.207L427.531 326.028V331.655L422.646 334.482L417.755 331.655Z"/>
<path id="land_480" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 343.205V337.573L429.38 334.751L434.271 337.573V343.205L429.38 346.021L424.495 343.205Z"/>
<path id="land_481" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 354.743V349.116L436.12 346.295L441.01 349.116V354.743L436.12 357.564L431.234 354.743Z"/>
<path id="land_482" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M276.255 66.1632V60.5367L281.146 57.7151L286.031 60.5367V66.1632L281.146 68.9848L276.255 66.1632Z"/>
<path id="land_483" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M283.001 77.7127V72.0751L287.886 69.2534L292.771 72.0751V77.7127L287.886 80.5288L283.001 77.7127Z"/>
<path id="land_484" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 89.2513V83.6192L294.625 80.7976L299.51 83.6192V89.2513L294.625 92.073L289.734 89.2513Z"/>
<path id="land_485" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 100.795V95.1634L301.359 92.3418L306.249 95.1634V100.795L301.359 103.617L296.474 100.795Z"/>
<path id="land_486" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 112.339V106.702L308.098 103.886L312.983 106.707V112.339L308.098 115.161L303.213 112.339Z"/>
<path id="land_487" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 123.883V118.246L314.838 115.43L319.723 118.246V123.883L314.838 126.7L309.952 123.883Z"/>
<path id="land_488" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 135.422V129.796L321.577 126.974L326.462 129.796V135.422L321.572 138.244L316.687 135.422Z"/>
<path id="land_489" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 146.966V141.334L328.311 138.518L333.202 141.334V146.966L328.311 149.788L323.426 146.966Z"/>
<path id="land_490" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M332.608 159.927L330.16 158.51V152.884L335.05 150.062L339.935 152.878V158.516L335.05 161.332L332.608 159.927Z"/>
<path id="land_491" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 170.054V164.422L341.79 161.601L346.675 164.422V170.054L341.79 172.87L336.899 170.054Z"/>
<path id="land_492" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 181.599V175.966L348.529 173.145L353.415 175.966V181.599L348.529 184.42L343.639 181.599Z"/>
<path id="land_493" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 193.137V187.51L355.263 184.689L360.154 187.51V193.137L355.263 195.958L350.378 193.137Z"/>
<path id="land_494" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 204.686V199.054L362.003 196.233L366.893 199.054V204.686L362.003 207.503L357.112 204.686Z"/>
<path id="land_495" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 216.225V210.593L368.742 207.777L373.627 210.593V216.225L368.742 219.047L363.857 216.225Z"/>
<path id="land_496" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 227.769V222.143L375.481 219.321L380.366 222.137V227.775L375.481 230.591L370.591 227.769Z"/>
<path id="land_497" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 239.313V233.681L382.215 230.865L387.106 233.681V239.313L382.215 242.141L377.33 239.313Z"/>
<path id="land_498" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 250.857V245.225L386.512 243.809L388.955 242.404L393.84 245.225V250.857L388.955 253.673L384.07 250.857Z"/>
<path id="land_499" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 262.396V256.769L395.694 253.948L400.579 256.769V262.402L395.694 265.218L390.804 262.396Z"/>
<path id="land_500" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 273.945V268.313L402.434 265.492L407.319 268.313V273.945L402.434 276.761L397.543 273.945Z"/>
<path id="land_501" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 285.484V279.857L409.167 277.036L414.058 279.852V285.49L409.167 288.306L404.282 285.484Z"/>
<path id="land_502" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 297.034V291.396L413.464 289.985L415.907 288.58L420.792 291.396V297.034L415.907 299.85L411.022 297.034Z"/>
<path id="land_503" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 308.572V302.94L422.646 300.118L427.531 302.94V308.572L422.646 311.394L417.755 308.572Z"/>
<path id="land_504" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 320.116V314.49L429.38 311.663L434.271 314.49V320.116L429.38 322.938L424.495 320.116Z"/>
<path id="land_505" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 331.66V326.028L436.12 323.207L441.01 326.028V331.655L436.12 334.482L431.234 331.66Z"/>
<path id="land_506" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 343.205V337.567L442.859 334.751L447.744 337.567V343.205L442.859 346.021L437.974 343.205Z"/>
<path id="land_507" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 354.748V349.111L449.599 346.295L454.484 349.116V354.743L449.599 357.564L444.708 354.748Z"/>
<path id="land_508" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M289.734 66.1688V60.5311L294.625 57.7151L299.51 60.5367V66.1632L294.625 68.9848L289.734 66.1688Z"/>
<path id="land_509" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M296.474 77.7127V72.0807L301.359 69.2534L306.249 72.0807V77.7071L301.359 80.5288L296.474 77.7127Z"/>
<path id="land_510" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 89.2513V83.6192L308.098 80.7976L312.983 83.6192V89.2513L308.098 92.073L303.213 89.2513Z"/>
<path id="land_511" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 100.795V95.1634L314.838 92.3418L319.723 95.1634V100.795L314.838 103.617L309.952 100.795Z"/>
<path id="land_512" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 112.334V106.707L321.577 103.886L326.462 106.707V112.334L321.572 115.155L316.687 112.334Z"/>
<path id="land_513" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 123.883V118.246L328.311 115.43L333.202 118.246V123.883L328.311 126.7L323.426 123.883Z"/>
<path id="land_514" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 135.422V129.796L335.05 126.974L339.935 129.79V135.428L335.05 138.244L330.16 135.422Z"/>
<path id="land_515" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 146.966V141.334L341.79 138.518L346.675 141.334V146.966L341.79 149.788L336.899 146.966Z"/>
<path id="land_516" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 158.51V152.878L348.529 150.062L353.415 152.884V158.51L348.529 161.332L343.639 158.51Z"/>
<path id="land_517" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 170.054V164.422L355.263 161.601L360.154 164.422V170.054L355.263 172.87L350.378 170.054Z"/>
<path id="land_518" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 181.599V175.966L362.003 173.145L366.893 175.966V181.599L362.003 184.42L357.112 181.599Z"/>
<path id="land_519" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 193.137V187.51L368.742 184.689L373.627 187.51V193.142L368.742 195.958L363.857 193.137Z"/>
<path id="land_520" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 204.686V199.054L375.481 196.233L380.366 199.054V204.686L375.481 207.503L370.591 204.686Z"/>
<path id="land_521" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 216.225V210.599L382.215 207.777L387.106 210.599V216.225L382.215 219.047L377.33 216.225Z"/>
<path id="land_522" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 227.775V222.137L388.955 219.315L393.84 222.137V227.775L388.955 230.591L384.07 227.775Z"/>
<path id="land_523" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 239.313V233.681L395.694 230.865L400.579 233.681V239.313L395.694 242.135L390.804 239.313Z"/>
<path id="land_524" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 250.857V245.225L402.434 242.404L407.319 245.225V250.857L402.434 253.673L397.543 250.857Z"/>
<path id="land_525" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 262.396V256.769L409.167 253.948L411.604 255.359L414.058 256.769V262.402L409.167 265.218L404.282 262.396Z"/>
<path id="land_526" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 273.945V268.308L415.907 265.492L420.792 268.308V273.945L415.907 276.761L411.022 273.945Z"/>
<path id="land_527" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 285.49V279.852L422.646 277.036L427.531 279.857V285.484L422.646 288.306L417.755 285.49Z"/>
<path id="land_528" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 297.028V291.402L429.38 288.58L434.271 291.402V297.028L429.38 299.85L424.495 297.028Z"/>
<path id="land_529" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 308.572V302.94L436.12 300.118L441.01 302.94V308.572L436.12 311.394L431.234 308.572Z"/>
<path id="land_530" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 320.122V314.484L442.859 311.663L447.744 314.484V320.122L442.859 322.938L437.974 320.122Z"/>
<path id="land_531" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 331.66V326.028L449.599 323.207L454.484 326.028V331.66L449.599 334.482L444.708 331.66Z"/>
<path id="land_532" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 343.205V337.573L456.332 334.751L461.223 337.573V343.205L456.332 346.021L451.447 343.205Z"/>
<path id="land_533" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 354.743V349.116L463.072 346.295L467.962 349.116V354.743L463.072 357.564L458.187 354.743Z"/>
<path id="land_534" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M303.213 66.1688V60.5311L308.098 57.7151L312.983 60.5367V66.1632L308.098 68.9848L303.213 66.1688Z"/>
<path id="land_535" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M309.952 77.7127V72.0807L314.838 69.2534L319.723 72.0807V77.7127L314.838 80.5288L309.952 77.7127Z"/>
<path id="land_536" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 89.2513V83.6192L321.577 80.7976L326.462 83.6192V89.2513L321.572 92.073L316.687 89.2513Z"/>
<path id="land_537" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 100.795V95.1634L328.311 92.3418L333.202 95.1634V100.795L328.311 103.617L323.426 100.795Z"/>
<path id="land_538" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 112.339V106.707L335.05 103.886L339.935 106.702V112.339L335.05 115.161L330.16 112.339Z"/>
<path id="land_539" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 123.883V118.251L341.79 115.43L346.675 118.251V123.883L341.79 126.7L336.899 123.883Z"/>
<path id="land_540" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 135.428V129.79L348.529 126.974L353.415 129.796V135.422L348.529 138.244L343.639 135.428Z"/>
<path id="land_541" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 146.966V141.334L355.263 138.518L360.154 141.334V146.966L355.263 149.788L350.378 146.966Z"/>
<path id="land_542" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 158.51V152.884L362.003 150.062L366.893 152.878V158.51L362.003 161.332L357.112 158.51Z"/>
<path id="land_543" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 170.054V164.422L368.742 161.601L373.627 164.422V170.054L368.742 172.87L363.857 170.054Z"/>
<path id="land_544" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 181.599V175.966L375.481 173.145L380.366 175.966V181.599L375.481 184.42L370.591 181.599Z"/>
<path id="land_545" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 193.137V187.51L382.215 184.689L387.106 187.51V193.137L382.215 195.958L377.33 193.137Z"/>
<path id="land_546" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 204.686V199.049L388.955 196.233L393.84 199.054V204.686L388.955 207.503L384.07 204.686Z"/>
<path id="land_547" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 216.225V210.599L395.694 207.777L400.579 210.593V216.225L395.694 219.047L390.804 216.225Z"/>
<path id="land_548" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 227.769V222.143L402.434 219.321L407.319 222.137V227.775L402.434 230.591L397.543 227.769Z"/>
<path id="land_549" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 239.313V233.681L409.167 230.865L414.058 233.681V239.313L409.167 242.135L404.282 239.313Z"/>
<path id="land_550" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 250.857V245.225L413.464 243.809L415.907 242.404L420.792 245.225V250.857L415.907 253.673L411.022 250.857Z"/>
<path id="land_551" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 262.402V256.769L422.646 253.948L427.531 256.769V262.396L422.646 265.218L417.755 262.402Z"/>
<path id="land_552" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 273.945V268.313L429.38 265.492L434.271 268.313V273.945L429.38 276.761L424.495 273.945Z"/>
<path id="land_553" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 285.49V279.852L436.12 277.036L441.01 279.857V285.484L436.12 288.306L431.234 285.49Z"/>
<path id="land_554" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 297.034V291.396L442.859 288.58L447.744 291.402V297.034L442.859 299.85L437.974 297.034Z"/>
<path id="land_555" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 308.572V302.94L449.599 300.118L454.484 302.94V308.572L449.599 311.394L444.708 308.572Z"/>
<path id="land_556" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 320.116V314.49L456.332 311.663L461.223 314.49V320.116L456.332 322.932L451.447 320.116Z"/>
<path id="land_557" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 331.655V326.028L463.072 323.207L467.962 326.028V331.655L463.072 334.482L458.187 331.655Z"/>
<path id="land_558" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 343.205V337.567L469.811 334.751L474.696 337.567V343.205L469.811 346.021L464.926 343.205Z"/>
<path id="land_559" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 354.743V349.116L476.551 346.295L481.436 349.116V354.743L476.551 357.564L471.66 354.743Z"/>
<path id="land_560" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M316.687 66.1632V60.5367L321.577 57.7151L326.462 60.5367V66.1632L321.572 68.9848L316.687 66.1632Z"/>
<path id="land_561" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M323.426 77.7127V72.0807L328.311 69.2534L333.202 72.0807V77.7071L328.311 80.5288L323.426 77.7127Z"/>
<path id="land_562" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 89.2513V83.6192L335.05 80.7976L339.935 83.6192V89.2513L335.05 92.073L330.16 89.2513Z"/>
<path id="land_563" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 100.795V95.1634L341.79 92.3418L346.675 95.1634V100.795L341.79 103.617L336.899 100.795Z"/>
<path id="land_564" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 112.339V106.707L348.529 103.886L353.415 106.707V112.339L348.529 115.161L343.639 112.339Z"/>
<path id="land_565" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 123.883V118.251L355.263 115.43L360.154 118.251V123.883L355.263 126.7L350.378 123.883Z"/>
<path id="land_566" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 135.422V129.796L362.003 126.974L366.893 129.79V135.428L362.003 138.244L357.112 135.422Z"/>
<path id="land_567" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 146.966V141.334L368.742 138.518L373.627 141.334V146.966L368.742 149.788L363.857 146.966Z"/>
<path id="land_568" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 158.51V152.884L375.481 150.062L380.366 152.878V158.51L375.481 161.332L370.591 158.51Z"/>
<path id="land_569" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 170.054V164.422L382.215 161.601L387.106 164.422V170.054L382.215 172.876L377.33 170.054Z"/>
<path id="land_570" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 181.599V175.966L388.955 173.145L393.84 175.966V181.599L388.955 184.42L384.07 181.599Z"/>
<path id="land_571" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 193.137V187.51L395.694 184.689L400.579 187.51V193.137L395.694 195.958L390.804 193.137Z"/>
<path id="land_572" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 204.681V199.054L402.434 196.233L407.319 199.054V204.686L402.434 207.503L397.543 204.681Z"/>
<path id="land_573" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 216.225V210.599L409.167 207.777L414.058 210.599V216.225L409.167 219.047L404.282 216.225Z"/>
<path id="land_574" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 227.775V222.137L415.907 219.315L420.792 222.137V227.775L415.907 230.591L411.022 227.775Z"/>
<path id="land_575" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 239.313V233.681L422.646 230.865L427.531 233.681V239.313L422.646 242.141L417.755 239.313Z"/>
<path id="land_576" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 250.857V245.225L429.38 242.404L434.271 245.225V250.857L429.38 253.673L424.495 250.857Z"/>
<path id="land_577" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 262.402V256.769L436.12 253.948L441.01 256.769V262.396L436.12 265.218L431.234 262.402Z"/>
<path id="land_578" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 273.945V268.308L442.859 265.492L447.744 268.313V273.945L442.859 276.761L437.974 273.945Z"/>
<path id="land_579" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 285.49V279.852L449.599 277.036L454.484 279.852V285.49L449.599 288.306L444.708 285.49Z"/>
<path id="land_580" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 297.028V291.402L453.895 289.985L456.332 288.58L461.223 291.402V297.028L456.332 299.85L451.447 297.028Z"/>
<path id="land_581" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 308.572V302.94L463.072 300.118L467.962 302.94V308.572L463.072 311.394L458.187 308.572Z"/>
<path id="land_582" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 320.122V314.484L469.811 311.663L474.696 314.484V320.122L469.811 322.932L464.926 320.122Z"/>
<path id="land_583" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 331.655V326.028L476.551 323.207L481.436 326.028V331.655L476.551 334.482L471.66 331.655Z"/>
<path id="land_584" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 343.205V337.567L483.285 334.751L488.175 337.573V343.205L483.285 346.021L478.399 343.205Z"/>
<path id="land_585" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 354.743V349.116L490.024 346.295L494.909 349.116V354.743L490.024 357.564L485.133 354.743Z"/>
<path id="land_586" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 366.287V360.66L496.763 357.839L501.648 360.655V366.293L496.763 369.109L491.878 366.287Z"/>
<path id="land_587" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 377.831V372.199L503.503 369.383L508.388 372.199V377.831L503.503 380.653L498.612 377.831Z"/>
<path id="land_588" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 389.375V383.749L510.242 380.921L515.128 383.743V389.375L510.242 392.197L505.352 389.375Z"/>
<path id="land_589" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 447.09V441.458L543.928 438.642L548.813 441.458V447.09L543.928 449.912L539.038 447.09Z"/>
<path id="land_590" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 458.64V453.002L550.668 450.186L555.553 453.008V458.634L550.668 461.456L545.782 458.64Z"/>
<path id="land_591" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 470.178V464.546L557.407 461.73L562.292 464.546V470.178L557.407 473L552.516 470.178Z"/>
<path id="land_592" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M330.16 66.1632V60.5367L335.05 57.7151L339.935 60.5311V66.1688L335.05 68.9848L330.16 66.1632Z"/>
<path id="land_593" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 77.7071V72.0807L341.79 69.2534L346.675 72.0807V77.7071L341.79 80.5288L336.899 77.7071Z"/>
<path id="land_594" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 89.2513V83.6192L348.529 80.7976L353.415 83.6192V89.2513L348.529 92.073L343.639 89.2513Z"/>
<path id="land_595" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 100.795V95.1634L355.263 92.3418L360.154 95.1634V100.795L355.263 103.617L350.378 100.795Z"/>
<path id="land_596" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 112.334V106.707L362.003 103.886L366.893 106.707V112.339L362.003 115.161L357.112 112.334Z"/>
<path id="land_597" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 123.883V118.246L368.742 115.43L373.627 118.246V123.883L368.742 126.7L363.857 123.883Z"/>
<path id="land_598" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 135.422V129.796L375.481 126.974L380.366 129.79V135.428L375.481 138.244L370.591 135.422Z"/>
<path id="land_599" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 146.966V141.334L382.215 138.518L387.106 141.334V146.966L382.215 149.788L377.33 146.966Z"/>
<path id="land_600" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M386.512 159.927L384.07 158.516V152.878L388.955 150.062L393.84 152.878V158.51L388.955 161.332L386.512 159.927Z"/>
<path id="land_601" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 170.054V164.422L395.694 161.601L400.579 164.422V170.054L395.694 172.87L390.804 170.054Z"/>
<path id="land_602" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 181.593V175.966L402.434 173.145L407.319 175.966V181.599L402.434 184.42L397.543 181.593Z"/>
<path id="land_603" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 193.137V187.51L409.167 184.689L411.604 186.099L414.058 187.51V193.137L409.167 195.958L404.282 193.137Z"/>
<path id="land_604" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 204.686V199.049L415.907 196.233L420.792 199.049V204.686L415.907 207.503L411.022 204.686Z"/>
<path id="land_605" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 216.225V210.599L422.646 207.777L427.531 210.599V216.225L422.646 219.047L417.755 216.225Z"/>
<path id="land_606" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 227.769V222.143L429.38 219.321L434.271 222.143V227.769L429.38 230.591L424.495 227.769Z"/>
<path id="land_607" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 239.313V233.681L436.12 230.865L441.01 233.681V239.313L436.12 242.135L431.234 239.313Z"/>
<path id="land_608" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 250.857V245.225L442.859 242.404L447.744 245.225V250.857L442.859 253.673L437.974 250.857Z"/>
<path id="land_609" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 262.402V256.769L449.599 253.948L454.484 256.769V262.402L449.599 265.218L444.708 262.402Z"/>
<path id="land_610" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 273.945V268.313L456.332 265.492L461.223 268.313V273.945L456.332 276.761L451.447 273.945Z"/>
<path id="land_611" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 285.49V279.852L463.072 277.03L467.962 279.852V285.49L463.072 288.306L458.187 285.49Z"/>
<path id="land_612" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 297.034V291.396L469.811 288.58L474.696 291.396V297.034L469.811 299.85L464.926 297.034Z"/>
<path id="land_613" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 308.572V302.94L476.551 300.124L481.436 302.94V308.572L476.551 311.394L471.66 308.572Z"/>
<path id="land_614" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 320.116V314.484L483.285 311.663L488.175 314.49V320.116L483.285 322.938L478.399 320.116Z"/>
<path id="land_615" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 331.655V326.028L490.024 323.207L494.909 326.028V331.655L490.024 334.482L485.133 331.655Z"/>
<path id="land_616" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 343.205V337.567L496.763 334.751L501.648 337.567V343.205L496.763 346.021L491.878 343.205Z"/>
<path id="land_617" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 354.743V349.116L503.503 346.295L508.388 349.116V354.743L503.503 357.564L498.612 354.743Z"/>
<path id="land_618" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 366.287V360.66L510.242 357.839L515.128 360.66V366.287L510.242 369.109L505.352 366.287Z"/>
<path id="land_619" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 377.831V372.199L516.976 369.383L521.861 372.199V377.831L516.976 380.653L512.091 377.831Z"/>
<path id="land_620" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 400.919V395.287L530.455 392.471L535.34 395.287V400.919L530.455 403.741L525.564 400.919Z"/>
<path id="land_621" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 412.458V406.831L537.189 404.01L542.08 406.831V412.463L537.189 415.285L532.298 412.458Z"/>
<path id="land_622" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 424.008V418.37L543.928 415.554L548.813 418.37V424.008L543.928 426.824L539.038 424.008Z"/>
<path id="land_623" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 435.552V429.914L550.668 427.092L555.553 429.92V435.546L550.668 438.368L545.782 435.552Z"/>
<path id="land_624" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 447.09V441.458L557.407 438.642L562.292 441.458V447.09L557.407 449.912L552.516 447.09Z"/>
<path id="land_625" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 458.634V453.008L564.141 450.186L569.032 453.008V458.634L564.141 461.456L559.256 458.634Z"/>
<path id="land_626" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 470.178V464.546L570.88 461.73L575.766 464.546V470.178L570.88 473L565.995 470.178Z"/>
<path id="land_627" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M336.899 54.6246V48.9925L341.79 46.1709L346.675 48.9925V54.6246L341.79 57.4407L336.899 54.6246Z"/>
<path id="land_628" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M343.639 66.1688V60.5367L348.529 57.7151L353.415 60.5367V66.1632L348.529 68.9848L343.639 66.1688Z"/>
<path id="land_629" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 77.7071V72.0807L355.263 69.2534L360.154 72.0807V77.7071L355.263 80.5288L350.378 77.7071Z"/>
<path id="land_630" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 89.2513V83.6192L362.003 80.7976L366.893 83.6192V89.2513L362.003 92.073L357.112 89.2513Z"/>
<path id="land_631" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 100.795V95.1634L368.742 92.3418L373.627 95.1634V100.795L368.742 103.617L363.857 100.795Z"/>
<path id="land_632" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 112.334V106.707L375.481 103.886L380.366 106.707V112.339L375.481 115.161L370.591 112.334Z"/>
<path id="land_633" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 123.883V118.251L382.215 115.43L387.106 118.251V123.883L382.215 126.7L377.33 123.883Z"/>
<path id="land_634" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 135.428V129.79L388.955 126.974L393.84 129.79V135.428L388.955 138.244L384.07 135.428Z"/>
<path id="land_635" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 146.966V141.334L395.694 138.518L400.579 141.334V146.966L395.694 149.788L390.804 146.966Z"/>
<path id="land_636" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 158.51V152.884L402.434 150.062L407.319 152.878V158.51L402.434 161.332L397.543 158.51Z"/>
<path id="land_637" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 170.054V164.422L409.167 161.601L414.058 164.422V170.054L409.167 172.87L404.282 170.054Z"/>
<path id="land_638" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 181.599V175.966L415.907 173.145L420.792 175.966V181.599L415.907 184.42L411.022 181.599Z"/>
<path id="land_639" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 193.137V187.51L422.646 184.689L427.531 187.51V193.137L422.646 195.958L417.755 193.137Z"/>
<path id="land_640" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 204.686V199.054L429.38 196.233L434.271 199.054V204.686L429.38 207.503L424.495 204.686Z"/>
<path id="land_641" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 216.225V210.593L436.12 207.777L441.01 210.599V216.225L436.12 219.047L431.234 216.225Z"/>
<path id="land_642" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 227.775V222.137L442.859 219.315L447.744 222.137V227.769L442.859 230.591L437.974 227.775Z"/>
<path id="land_643" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 239.313V233.681L449.599 230.865L454.484 233.681V239.313L449.599 242.141L444.708 239.313Z"/>
<path id="land_644" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 250.857V245.225L453.895 243.809L456.332 242.409L461.223 245.225V250.857L456.332 253.673L451.447 250.857Z"/>
<path id="land_645" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 262.402V256.769L463.072 253.948L467.962 256.769V262.402L463.072 265.218L458.187 262.402Z"/>
<path id="land_646" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 273.945V268.308L469.811 265.492L474.696 268.313V273.945L469.811 276.761L464.926 273.945Z"/>
<path id="land_647" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 285.49V279.852L476.551 277.036L481.436 279.857V285.484L476.551 288.306L471.66 285.49Z"/>
<path id="land_648" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 297.028V291.402L483.285 288.58L488.175 291.402V297.028L483.285 299.85L478.399 297.028Z"/>
<path id="land_649" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 308.572V302.94L490.024 300.118L494.909 302.94V308.572L490.024 311.394L485.133 308.572Z"/>
<path id="land_650" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 320.116V314.484L496.763 311.663L501.648 314.484V320.122L496.763 322.932L491.878 320.116Z"/>
<path id="land_651" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 331.66V326.028L503.503 323.207L508.388 326.028V331.66L503.503 334.482L498.612 331.66Z"/>
<path id="land_652" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 343.205V337.567L510.242 334.751L515.128 337.567V343.205L510.242 346.021L505.352 343.205Z"/>
<path id="land_653" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 354.743V349.116L516.976 346.295L521.861 349.116V354.743L516.976 357.564L512.091 354.743Z"/>
<path id="land_654" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 366.287V360.66L523.715 357.839L528.6 360.66V366.293L523.715 369.109L518.83 366.287Z"/>
<path id="land_655" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 377.831V372.199L530.455 369.383L535.34 372.199V377.831L530.455 380.653L525.564 377.831Z"/>
<path id="land_656" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 389.37V383.749L537.189 380.921L542.08 383.749V389.375L537.189 392.197L532.298 389.37Z"/>
<path id="land_657" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 400.919V395.287L543.928 392.471L548.813 395.287V400.919L543.928 403.741L539.038 400.919Z"/>
<path id="land_658" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 412.463V406.831L550.668 404.01L555.553 406.831V412.463L550.668 415.285L545.782 412.463Z"/>
<path id="land_659" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 424.008V418.37L557.407 415.554L562.292 418.37V424.008L557.407 426.824L552.516 424.008Z"/>
<path id="land_660" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 435.546V429.92L564.141 427.092L569.032 429.92V435.546L564.141 438.368L559.256 435.546Z"/>
<path id="land_661" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 447.096V441.458L570.88 438.642L575.766 441.458V447.09L570.88 449.912L565.995 447.096Z"/>
<path id="land_662" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 458.634V453.002L577.62 450.186L582.505 453.008V458.634L577.62 461.456L572.729 458.634Z"/>
<path id="land_663" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M350.378 54.6246V48.9925L355.263 46.1709L360.154 48.9925V54.6246L355.263 57.4407L350.378 54.6246Z"/>
<path id="land_664" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M357.112 66.1632V60.5367L362.003 57.7151L366.893 60.5367V66.1688L362.003 68.9848L357.112 66.1632Z"/>
<path id="land_665" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 77.7071V72.0807L368.742 69.2534L373.627 72.0807V77.7127L368.742 80.5288L363.857 77.7071Z"/>
<path id="land_666" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 89.2513V83.6192L375.481 80.7976L380.366 83.6192V89.2513L375.481 92.073L370.591 89.2513Z"/>
<path id="land_667" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 100.795V95.1634L382.215 92.3418L387.106 95.1634V100.795L382.215 103.617L377.33 100.795Z"/>
<path id="land_668" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 112.339V106.702L388.955 103.886L393.84 106.707V112.339L388.955 115.161L384.07 112.339Z"/>
<path id="land_669" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 123.883V118.251L395.694 115.43L400.579 118.246V123.883L395.694 126.7L390.804 123.883Z"/>
<path id="land_670" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 135.422V129.796L402.434 126.974L407.319 129.79V135.428L402.434 138.244L397.543 135.422Z"/>
<path id="land_671" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 146.966V141.334L409.167 138.518L414.058 141.334V146.966L409.167 149.788L404.282 146.966Z"/>
<path id="land_672" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 158.516V152.878L415.907 150.062L420.792 152.878V158.516L415.907 161.332L411.022 158.516Z"/>
<path id="land_673" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 170.054V164.422L422.646 161.601L427.531 164.422V170.054L422.646 172.876L417.755 170.054Z"/>
<path id="land_674" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 181.599V175.966L429.38 173.145L434.271 175.966V181.599L429.38 184.42L424.495 181.599Z"/>
<path id="land_675" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 193.137V187.51L436.12 184.689L441.01 187.51V193.137L436.12 195.958L431.234 193.137Z"/>
<path id="land_676" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 204.686V199.049L442.859 196.233L447.744 199.054V204.686L442.859 207.503L437.974 204.686Z"/>
<path id="land_677" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 216.225V210.593L449.599 207.777L454.484 210.593V216.225L449.599 219.047L444.708 216.225Z"/>
<path id="land_678" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 227.769V222.143L453.895 220.726L456.332 219.321L461.223 222.143V227.769L456.332 230.591L451.447 227.769Z"/>
<path id="land_679" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 239.313V233.681L463.072 230.865L467.962 233.681V239.313L463.072 242.141L458.187 239.313Z"/>
<path id="land_680" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 250.857V245.225L469.811 242.404L474.696 245.225V250.857L469.811 253.673L464.926 250.857Z"/>
<path id="land_681" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 262.402V256.769L476.551 253.948L481.436 256.769V262.396L476.551 265.218L471.66 262.402Z"/>
<path id="land_682" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 273.945V268.313L483.285 265.492L488.175 268.313V273.945L483.285 276.761L478.399 273.945Z"/>
<path id="land_683" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 285.484V279.857L490.024 277.036L494.909 279.852V285.49L490.024 288.306L485.133 285.484Z"/>
<path id="land_684" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 297.028V291.402L496.763 288.58L501.648 291.396V297.034L496.763 299.85L491.878 297.028Z"/>
<path id="land_685" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 308.572V302.94L503.503 300.118L508.388 302.94V308.572L503.503 311.394L498.612 308.572Z"/>
<path id="land_686" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 320.116V314.484L510.242 311.663L515.128 314.484V320.122L510.242 322.938L505.352 320.116Z"/>
<path id="land_687" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 331.655V326.028L516.976 323.207L521.861 326.028V331.66L516.976 334.482L512.091 331.655Z"/>
<path id="land_688" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 343.205V337.567L523.715 334.751L528.6 337.567V343.205L523.715 346.021L518.83 343.205Z"/>
<path id="land_689" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 354.743V349.116L530.455 346.295L535.34 349.116V354.743L530.455 357.564L525.564 354.743Z"/>
<path id="land_690" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 366.287V360.66L537.189 357.839L542.08 360.66V366.287L537.189 369.109L532.298 366.287Z"/>
<path id="land_691" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 377.831V372.199L543.928 369.383L548.813 372.199V377.831L543.928 380.653L539.038 377.831Z"/>
<path id="land_692" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 389.375V383.743L550.668 380.921L555.553 383.749V389.375L550.668 392.197L545.782 389.375Z"/>
<path id="land_693" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 400.919V395.287L557.407 392.471L562.292 395.287V400.919L557.407 403.741L552.516 400.919Z"/>
<path id="land_694" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 412.463V406.831L564.141 404.01L569.032 406.831V412.458L564.141 415.285L559.256 412.463Z"/>
<path id="land_695" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 424.008V418.37L570.88 415.554L575.766 418.37V424.008L570.88 426.824L565.995 424.008Z"/>
<path id="land_696" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 435.546V429.919L577.62 427.098L582.505 429.919V435.546L577.62 438.367L572.729 435.546Z"/>
<path id="land_697" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 66.1632V60.5367L375.481 57.7151L380.366 60.5367V66.1688L375.481 68.9848L370.591 66.1632Z"/>
<path id="land_698" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 77.7071V72.0807L382.215 69.2534L387.106 72.0807V77.7071L382.215 80.5288L377.33 77.7071Z"/>
<path id="land_699" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 89.2513V83.6192L388.955 80.7976L393.84 83.6192V89.2513L388.955 92.073L384.07 89.2513Z"/>
<path id="land_700" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 100.795V95.1634L395.694 92.3418L400.579 95.1634V100.795L395.694 103.617L390.804 100.795Z"/>
<path id="land_701" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 112.334V106.707L402.434 103.886L407.319 106.707V112.339L402.434 115.161L397.543 112.334Z"/>
<path id="land_702" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 123.883V118.251L409.167 115.43L414.058 118.251V123.883L409.167 126.7L404.282 123.883Z"/>
<path id="land_703" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 135.428V129.79L415.907 126.974L420.792 129.79V135.428L415.907 138.244L411.022 135.428Z"/>
<path id="land_704" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 146.966V141.334L422.646 138.518L427.531 141.334V146.966L422.646 149.788L417.755 146.966Z"/>
<path id="land_705" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 158.51V152.884L429.38 150.062L434.271 152.884V158.51L429.38 161.332L424.495 158.51Z"/>
<path id="land_706" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 170.054V164.422L436.12 161.601L441.01 164.422V170.054L436.12 172.87L431.234 170.054Z"/>
<path id="land_707" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 181.599V175.966L442.859 173.145L447.744 175.966V181.599L442.859 184.42L437.974 181.599Z"/>
<path id="land_708" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 193.142V187.51L449.599 184.689L454.484 187.51V193.137L449.599 195.958L444.708 193.142Z"/>
<path id="land_709" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 204.686V199.054L456.332 196.233L461.223 199.054V204.686L456.332 207.503L451.447 204.686Z"/>
<path id="land_710" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 216.225V210.599L463.072 207.777L467.962 210.599V216.225L463.072 219.047L458.187 216.225Z"/>
<path id="land_711" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 227.775V222.137L469.811 219.321L474.696 222.137V227.775L469.811 230.591L464.926 227.775Z"/>
<path id="land_712" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 239.313V233.681L476.551 230.865L481.436 233.681V239.313L476.551 242.135L471.66 239.313Z"/>
<path id="land_713" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 250.857V245.225L483.285 242.404L488.175 245.225V250.857L483.285 253.673L478.399 250.857Z"/>
<path id="land_714" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 262.396V256.769L490.024 253.948L494.909 256.769V262.402L490.024 265.218L485.133 262.396Z"/>
<path id="land_715" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 273.945V268.313L496.763 265.492L501.648 268.313V273.945L496.763 276.761L491.878 273.945Z"/>
<path id="land_716" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 285.49V279.852L503.503 277.036L508.388 279.852V285.49L503.503 288.306L498.612 285.49Z"/>
<path id="land_717" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 297.028V291.402L510.242 288.58L515.128 291.402V297.034L510.242 299.85L505.352 297.028Z"/>
<path id="land_718" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 308.572V302.94L516.976 300.124L521.861 302.94V308.572L516.976 311.394L512.091 308.572Z"/>
<path id="land_719" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 320.122V314.484L523.715 311.663L528.6 314.484V320.122L523.715 322.938L518.83 320.122Z"/>
<path id="land_720" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 331.655V326.028L530.455 323.207L535.34 326.028V331.66L530.455 334.482L525.564 331.655Z"/>
<path id="land_721" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 343.205V337.573L537.189 334.751L542.08 337.567V343.205L537.189 346.021L532.298 343.205Z"/>
<path id="land_722" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 354.743V349.116L543.928 346.295L548.813 349.116V354.743L543.928 357.564L539.038 354.743Z"/>
<path id="land_723" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 366.293V360.655L550.668 357.839L555.553 360.66V366.287L550.668 369.109L545.782 366.293Z"/>
<path id="land_724" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 377.831V372.199L557.407 369.383L562.292 372.199V377.831L557.407 380.653L552.516 377.831Z"/>
<path id="land_725" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 389.375V383.749L564.141 380.921L569.032 383.749V389.375L564.141 392.197L559.256 389.375Z"/>
<path id="land_726" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 400.919V395.287L570.88 392.471L575.766 395.287V400.919L570.88 403.741L565.995 400.919Z"/>
<path id="land_727" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 412.463V406.831L577.62 404.01L582.505 406.831V412.463L577.62 415.28L572.729 412.463Z"/>
<path id="land_728" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M363.857 31.5362V25.9042L368.742 23.0825L373.627 25.9042V31.5362L368.742 34.3523L363.857 31.5362Z"/>
<path id="land_729" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M370.591 43.0804V37.4483L375.481 34.6267L380.366 37.4483V43.0804L375.481 45.8965L370.591 43.0804Z"/>
<path id="land_730" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 66.1688V60.5311L388.955 57.7151L393.84 60.5367V66.1688L388.955 68.9848L384.07 66.1688Z"/>
<path id="land_731" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 77.7071V72.0807L395.694 69.2534L400.579 72.0807V77.7127L395.694 80.5288L390.804 77.7071Z"/>
<path id="land_732" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 89.2513V83.6248L402.434 80.7976L407.319 83.6192V89.2513L402.434 92.073L397.543 89.2513Z"/>
<path id="land_733" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 100.795V95.1634L409.167 92.3418L414.058 95.1634V100.795L409.167 103.617L404.282 100.795Z"/>
<path id="land_734" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 112.339V106.702L415.907 103.886L420.792 106.702V112.339L415.907 115.161L411.022 112.339Z"/>
<path id="land_735" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 123.883V118.251L422.646 115.43L427.531 118.251V123.883L422.646 126.7L417.755 123.883Z"/>
<path id="land_736" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 135.422V129.796L429.38 126.974L434.271 129.796V135.422L429.38 138.244L424.495 135.422Z"/>
<path id="land_737" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 146.966V141.334L436.12 138.518L441.01 141.334V146.966L436.12 149.788L431.234 146.966Z"/>
<path id="land_738" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 158.516V152.878L442.859 150.062L447.744 152.878V158.51L442.859 161.332L437.974 158.516Z"/>
<path id="land_739" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 170.054V164.422L449.599 161.601L454.484 164.422V170.054L449.599 172.876L444.708 170.054Z"/>
<path id="land_740" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 181.599V175.966L456.332 173.145L461.223 175.966V181.599L456.332 184.42L451.447 181.599Z"/>
<path id="land_741" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 193.137V187.51L463.072 184.689L467.962 187.51V193.137L463.072 195.958L458.187 193.137Z"/>
<path id="land_742" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 204.686V199.049L469.811 196.233L474.696 199.049V204.686L469.811 207.503L464.926 204.686Z"/>
<path id="land_743" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 216.225V210.599L476.551 207.777L481.436 210.599V216.225L476.551 219.047L471.66 216.225Z"/>
<path id="land_744" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 227.769V222.143L483.285 219.321L488.175 222.143V227.769L483.285 230.591L478.399 227.769Z"/>
<path id="land_745" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 239.313V233.681L490.024 230.865L494.909 233.681V239.313L490.024 242.135L485.133 239.313Z"/>
<path id="land_746" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 250.857V245.225L496.763 242.404L501.648 245.225V250.857L496.763 253.673L491.878 250.857Z"/>
<path id="land_747" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 262.402V256.769L503.503 253.948L508.388 256.769V262.402L503.503 265.218L498.612 262.402Z"/>
<path id="land_748" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 273.945V268.313L510.242 265.492L515.128 268.313V273.945L510.242 276.761L505.352 273.945Z"/>
<path id="land_749" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 285.484V279.857L516.976 277.036L521.861 279.852V285.49L516.976 288.306L512.091 285.484Z"/>
<path id="land_750" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 297.034V291.402L523.715 288.58L528.6 291.396V297.034L523.715 299.85L518.83 297.034Z"/>
<path id="land_751" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 308.572V302.94L530.455 300.118L535.34 302.94V308.572L530.455 311.394L525.564 308.572Z"/>
<path id="land_752" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 320.116V314.49L537.189 311.663L542.08 314.484V320.116L537.189 322.938L532.298 320.116Z"/>
<path id="land_753" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 331.655V326.028L543.928 323.207L548.813 326.028V331.655L543.928 334.482L539.038 331.655Z"/>
<path id="land_754" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 343.205V337.567L550.668 334.751L555.553 337.567V343.205L550.668 346.021L545.782 343.205Z"/>
<path id="land_755" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 354.743V349.116L557.407 346.295L562.292 349.116V354.743L557.407 357.564L552.516 354.743Z"/>
<path id="land_756" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 366.287V360.66L564.141 357.839L569.032 360.66V366.287L564.141 369.109L559.256 366.287Z"/>
<path id="land_757" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 377.837V372.199L570.88 369.383L575.766 372.199V377.831L570.88 380.653L565.995 377.837Z"/>
<path id="land_758" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 389.375V383.743L577.62 380.921L582.505 383.749V389.375L577.62 392.191L572.729 389.375Z"/>
<path id="land_759" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 400.919V395.287L584.359 392.471L589.244 395.287V400.919L584.359 403.741L579.468 400.919Z"/>
<path id="land_760" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M377.33 31.5362V25.9098L382.215 23.0825L387.106 25.9098V31.5362L382.215 34.3523L377.33 31.5362Z"/>
<path id="land_761" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M384.07 43.0804V37.4483L388.955 34.6267L393.84 37.4483V43.0804L388.955 45.9021L384.07 43.0804Z"/>
<path id="land_762" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M390.804 54.6246V48.9925L395.694 46.1709L400.579 48.9925V54.6246L395.694 57.4407L390.804 54.6246Z"/>
<path id="land_763" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M397.543 66.1632V60.5367L402.434 57.7151L407.319 60.5367V66.1688L402.434 68.9848L397.543 66.1632Z"/>
<path id="land_764" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 77.7071V72.0807L409.167 69.2534L414.058 72.0807V77.7071L411.61 79.118L409.167 80.5288L404.282 77.7071Z"/>
<path id="land_765" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 89.2513V83.6192L415.907 80.7976L420.792 83.6192V89.2513L415.907 92.073L411.022 89.2513Z"/>
<path id="land_766" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 100.795V95.1634L422.646 92.3418L427.531 95.1634V100.795L422.646 103.617L417.755 100.795Z"/>
<path id="land_767" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 112.334V106.707L429.38 103.886L434.271 106.707V112.334L429.38 115.161L424.495 112.334Z"/>
<path id="land_768" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 123.883V118.246L436.12 115.43L441.01 118.251V123.883L436.12 126.7L431.234 123.883Z"/>
<path id="land_769" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 135.428V129.79L442.859 126.974L447.744 129.796V135.428L442.859 138.244L437.974 135.428Z"/>
<path id="land_770" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 146.966V141.334L449.599 138.518L454.484 141.334V146.966L449.599 149.788L444.708 146.966Z"/>
<path id="land_771" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 158.51V152.884L456.332 150.062L461.223 152.884V158.51L456.332 161.332L451.447 158.51Z"/>
<path id="land_772" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 170.054V164.422L463.072 161.601L467.962 164.422V170.054L463.072 172.876L458.187 170.054Z"/>
<path id="land_773" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 181.599V175.966L469.811 173.145L474.696 175.966V181.599L469.811 184.42L464.926 181.599Z"/>
<path id="land_774" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 193.137V187.51L476.551 184.689L481.436 187.51V193.137L476.551 195.958L471.66 193.137Z"/>
<path id="land_775" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 204.686V199.054L483.285 196.233L488.175 199.054V204.686L483.285 207.503L478.399 204.686Z"/>
<path id="land_776" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 216.225V210.599L490.024 207.777L494.909 210.599V216.225L490.024 219.047L485.133 216.225Z"/>
<path id="land_777" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 227.769V222.143L496.763 219.321L501.648 222.137V227.775L496.763 230.591L491.878 227.769Z"/>
<path id="land_778" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 239.313V233.681L503.503 230.865L508.388 233.681V239.313L503.503 242.141L498.612 239.313Z"/>
<path id="land_779" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 250.857V245.225L510.242 242.404L515.128 245.225V250.857L510.242 253.673L505.352 250.857Z"/>
<path id="land_780" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 262.396V256.769L516.976 253.948L521.861 256.769V262.402L516.976 265.218L512.091 262.396Z"/>
<path id="land_781" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 273.945V268.313L523.715 265.492L528.6 268.313V273.945L523.715 276.761L518.83 273.945Z"/>
<path id="land_782" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 285.484V279.858L530.455 277.03L535.34 279.852V285.49L530.455 288.306L525.564 285.484Z"/>
<path id="land_783" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 297.028V291.402L537.189 288.58L542.08 291.402V297.034L537.189 299.85L532.298 297.028Z"/>
<path id="land_784" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 308.572V302.94L543.928 300.118L548.813 302.94V308.572L543.928 311.394L539.038 308.572Z"/>
<path id="land_785" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 320.122V314.484L550.668 311.663L555.553 314.484V320.122L550.668 322.938L545.782 320.122Z"/>
<path id="land_786" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 331.655V326.028L557.407 323.207L562.292 326.028V331.655L557.407 334.482L552.516 331.655Z"/>
<path id="land_787" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 343.205V337.567L564.141 334.751L569.032 337.573V343.205L564.141 346.021L559.256 343.205Z"/>
<path id="land_788" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 354.748V349.111L570.88 346.295L575.766 349.116V354.743L570.88 357.564L565.995 354.748Z"/>
<path id="land_789" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 366.287V360.66L577.62 357.839L582.505 360.66V366.287L577.62 369.109L572.729 366.287Z"/>
<path id="land_790" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 377.837V372.199L584.359 369.383L589.244 372.199V377.831L584.359 380.653L579.468 377.837Z"/>
<path id="land_791" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 389.375V383.743L591.093 380.921L595.984 383.749V389.375L591.093 392.197L586.208 389.375Z"/>
<path id="land_792" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 400.919V395.287L597.833 392.466L602.718 395.287V400.919L597.833 403.741L592.947 400.919Z"/>
<path id="land_793" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M404.282 54.6246V48.9925L409.167 46.1709L414.058 48.9925V54.6246L409.167 57.4407L404.282 54.6246Z"/>
<path id="land_794" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 66.1688V60.5311L415.907 57.7151L420.792 60.5311V66.1688L415.907 68.9848L411.022 66.1688Z"/>
<path id="land_795" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 77.7071V72.0807L422.646 69.2534L427.531 72.0807V77.7071L422.646 80.5288L417.755 77.7071Z"/>
<path id="land_796" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 89.2513V83.6192L429.38 80.7976L434.271 83.6192V89.2513L429.38 92.073L424.495 89.2513Z"/>
<path id="land_797" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 100.795V95.1634L436.12 92.3418L441.01 95.1634V100.795L436.12 103.617L431.234 100.795Z"/>
<path id="land_798" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 112.339V106.702L442.859 103.886L447.744 106.707V112.339L442.859 115.161L437.974 112.339Z"/>
<path id="land_799" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 123.883V118.246L449.599 115.43L454.484 118.246V123.883L449.599 126.7L444.708 123.883Z"/>
<path id="land_800" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 135.422V129.796L456.332 126.974L461.223 129.796V135.422L456.332 138.244L451.447 135.422Z"/>
<path id="land_801" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 146.966V141.334L463.072 138.518L467.962 141.334V146.966L463.072 149.788L458.187 146.966Z"/>
<path id="land_802" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 158.516V152.878L469.811 150.062L474.696 152.878V158.516L469.811 161.332L464.926 158.516Z"/>
<path id="land_803" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 170.054V164.422L476.551 161.601L481.436 164.422V170.054L476.551 172.87L471.66 170.054Z"/>
<path id="land_804" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 181.599V175.966L483.285 173.145L488.175 175.966V181.599L483.285 184.42L478.399 181.599Z"/>
<path id="land_805" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 193.137V187.51L490.024 184.689L494.909 187.51V193.137L490.024 195.958L485.133 193.137Z"/>
<path id="land_806" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 204.686V199.054L496.763 196.233L501.648 199.054V204.686L496.763 207.503L491.878 204.686Z"/>
<path id="land_807" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 216.225V210.593L503.503 207.777L508.388 210.593V216.225L503.503 219.047L498.612 216.225Z"/>
<path id="land_808" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 227.769V222.143L510.242 219.315L515.128 222.137V227.769L510.242 230.591L505.352 227.769Z"/>
<path id="land_809" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 239.313V233.681L516.976 230.865L521.861 233.681V239.313L516.976 242.135L512.091 239.313Z"/>
<path id="land_810" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 250.857V245.225L523.715 242.404L528.6 245.225V250.857L523.715 253.673L518.83 250.857Z"/>
<path id="land_811" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 262.396V256.769L530.455 253.948L535.34 256.769V262.402L530.455 265.218L525.564 262.396Z"/>
<path id="land_812" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 273.94V268.313L537.189 265.492L542.08 268.313V273.945L537.189 276.761L532.298 273.94Z"/>
<path id="land_813" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 285.484V279.857L543.928 277.036L548.813 279.852V285.49L543.928 288.306L539.038 285.484Z"/>
<path id="land_814" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 297.034V291.396L550.668 288.58L553.11 289.985L555.553 291.402V297.034L550.668 299.85L545.782 297.034Z"/>
<path id="land_815" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 308.572V302.94L557.407 300.118L562.292 302.94V308.572L557.407 311.394L552.516 308.572Z"/>
<path id="land_816" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 320.122V314.484L564.141 311.663L569.032 314.49V320.116L564.141 322.938L559.256 320.122Z"/>
<path id="land_817" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 331.66V326.023L570.88 323.207L575.766 326.028V331.655L570.88 334.482L565.995 331.66Z"/>
<path id="land_818" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 343.205V337.567L577.62 334.751L582.505 337.567V343.205L577.62 346.021L572.729 343.205Z"/>
<path id="land_819" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 354.748V349.111L584.359 346.295L589.244 349.116V354.743L584.359 357.564L579.468 354.748Z"/>
<path id="land_820" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 366.287V360.66L591.093 357.839L595.984 360.66V366.287L591.093 369.109L586.208 366.287Z"/>
<path id="land_821" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 377.837V372.199L597.833 369.383L602.718 372.199V377.831L597.833 380.653L592.947 377.837Z"/>
<path id="land_822" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 389.375V383.743L604.572 380.921L609.457 383.743V389.375L604.572 392.197L599.681 389.375Z"/>
<path id="land_823" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M411.022 43.086V37.4427L415.907 34.6267L420.792 37.4427V43.086L415.907 45.9021L411.022 43.086Z"/>
<path id="land_824" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M417.755 54.6246V48.9925L422.646 46.1709L427.531 48.9925V54.6246L422.646 57.4407L417.755 54.6246Z"/>
<path id="land_825" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M424.495 66.1632V60.5367L429.38 57.7151L434.271 60.5367V66.1632L429.38 68.9848L424.495 66.1632Z"/>
<path id="land_826" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 77.7127V72.0807L436.12 69.2534L441.01 72.0807V77.7071L436.12 80.5288L431.234 77.7127Z"/>
<path id="land_827" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 89.2513V83.6192L442.859 80.7976L447.744 83.6192V89.2513L442.859 92.073L437.974 89.2513Z"/>
<path id="land_828" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 100.795V95.1634L449.599 92.3418L454.484 95.1634V100.795L449.599 103.617L444.708 100.795Z"/>
<path id="land_829" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 112.334V106.707L456.332 103.886L461.223 106.707V112.334L456.332 115.161L451.447 112.334Z"/>
<path id="land_830" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 123.883V118.251L463.072 115.43L467.962 118.251V123.883L463.072 126.7L458.187 123.883Z"/>
<path id="land_831" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 135.428V129.79L469.811 126.974L474.696 129.79V135.428L469.811 138.244L464.926 135.428Z"/>
<path id="land_832" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 146.966V141.334L476.551 138.518L481.436 141.334V146.966L476.551 149.788L471.66 146.966Z"/>
<path id="land_833" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 158.51V152.884L483.285 150.062L488.175 152.884V158.51L483.285 161.332L478.399 158.51Z"/>
<path id="land_834" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 170.054V164.422L490.024 161.601L494.909 164.422V170.054L490.024 172.87L485.133 170.054Z"/>
<path id="land_835" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 181.599V175.966L496.763 173.145L501.648 175.966V181.599L496.763 184.42L491.878 181.599Z"/>
<path id="land_836" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 193.137V187.51L503.503 184.689L508.388 187.51V193.137L503.503 195.958L498.612 193.137Z"/>
<path id="land_837" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 204.686V199.054L510.242 196.233L515.128 199.054V204.686L510.242 207.503L505.352 204.686Z"/>
<path id="land_838" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 216.225V210.599L516.976 207.777L521.861 210.593V216.225L516.976 219.047L512.091 216.225Z"/>
<path id="land_839" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 227.769V222.137L523.715 219.321L528.6 222.137V227.775L523.715 230.591L518.83 227.769Z"/>
<path id="land_840" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 239.313V233.681L530.455 230.865L535.34 233.681V239.313L530.455 242.141L525.564 239.313Z"/>
<path id="land_841" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 250.857V245.231L537.189 242.404L542.08 245.225V250.857L537.189 253.673L532.298 250.857Z"/>
<path id="land_842" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 262.396V256.769L543.928 253.948L548.813 256.769V262.402L543.928 265.218L539.038 262.396Z"/>
<path id="land_843" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 273.945V268.308L550.668 265.492L555.553 268.313V273.945L550.668 276.761L545.782 273.945Z"/>
<path id="land_844" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 285.49V279.852L557.407 277.03L562.292 279.852V285.49L557.407 288.306L552.516 285.49Z"/>
<path id="land_845" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 297.034V291.402L564.141 288.58L569.032 291.402V297.028L564.141 299.85L559.256 297.034Z"/>
<path id="land_846" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 308.572V302.94L570.88 300.118L575.766 302.94V308.572L570.88 311.394L565.995 308.572Z"/>
<path id="land_847" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 320.122V314.484L577.62 311.668L582.505 314.484V320.116L577.62 322.932L572.729 320.122Z"/>
<path id="land_848" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 331.66V326.028L584.359 323.207L589.244 326.028V331.66L584.359 334.482L579.468 331.66Z"/>
<path id="land_849" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 343.205V337.567L591.093 334.751L595.984 337.573V343.205L591.093 346.021L586.208 343.205Z"/>
<path id="land_850" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 354.748V349.111L597.833 346.295L602.718 349.116V354.743L597.833 357.57L592.947 354.748Z"/>
<path id="land_851" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 366.293V360.655L604.572 357.839L609.457 360.66V366.287L604.572 369.109L599.681 366.293Z"/>
<path id="land_852" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 377.831V372.199L611.311 369.383L616.197 372.205V377.831L611.311 380.653L606.421 377.831Z"/>
<path id="land_853" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 54.6246V48.9925L436.12 46.1709L441.01 48.9925V54.6246L436.12 57.4407L431.234 54.6246Z"/>
<path id="land_854" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 66.1688V60.5311L442.859 57.7151L447.744 60.5367V66.1632L442.859 68.9848L437.974 66.1688Z"/>
<path id="land_855" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 77.7127V72.0807L449.599 69.2534L454.484 72.0807V77.7127L449.599 80.5288L444.708 77.7127Z"/>
<path id="land_856" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 89.2513V83.6192L456.332 80.7976L461.223 83.6192V89.2513L456.332 92.073L451.447 89.2513Z"/>
<path id="land_857" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 100.795V95.1634L463.072 92.3418L467.962 95.1634V100.795L463.072 103.617L458.187 100.795Z"/>
<path id="land_858" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 112.339V106.702L469.811 103.886L474.696 106.702V112.339L469.811 115.161L464.926 112.339Z"/>
<path id="land_859" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 123.883V118.251L476.551 115.43L481.436 118.251V123.883L476.551 126.7L471.66 123.883Z"/>
<path id="land_860" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 135.422V129.796L483.285 126.974L488.175 129.796V135.422L483.285 138.244L478.399 135.422Z"/>
<path id="land_861" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 146.966V141.334L490.024 138.518L494.909 141.334V146.966L490.024 149.788L485.133 146.966Z"/>
<path id="land_862" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 158.51V152.884L496.763 150.062L501.648 152.878V158.51L496.763 161.332L491.878 158.51Z"/>
<path id="land_863" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 170.054V164.422L503.503 161.601L508.388 164.422V170.054L503.503 172.876L498.612 170.054Z"/>
<path id="land_864" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 181.599V175.966L510.242 173.145L515.128 175.966V181.599L510.242 184.42L505.352 181.599Z"/>
<path id="land_865" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 193.137V187.51L516.976 184.689L521.861 187.51V193.137L516.976 195.958L512.091 193.137Z"/>
<path id="land_866" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 204.686V199.054L523.715 196.233L528.6 199.054V204.686L523.715 207.503L518.83 204.686Z"/>
<path id="land_867" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 216.225V210.599L530.455 207.777L535.34 210.593V216.225L530.455 219.047L525.564 216.225Z"/>
<path id="land_868" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 227.769V222.143L537.189 219.321L542.08 222.143V227.769L537.189 230.591L532.298 227.769Z"/>
<path id="land_869" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 239.313V233.681L543.928 230.865L548.813 233.681V239.313L543.928 242.135L539.038 239.313Z"/>
<path id="land_870" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 250.857V245.225L550.668 242.404L555.553 245.225V250.857L550.668 253.673L545.782 250.857Z"/>
<path id="land_871" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 262.402V256.769L557.407 253.948L562.292 256.769V262.402L557.407 265.218L552.516 262.402Z"/>
<path id="land_872" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 273.945V268.313L564.141 265.492L569.032 268.313V273.945L564.141 276.761L559.256 273.945Z"/>
<path id="land_873" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 285.49V279.852L570.88 277.036L575.766 279.857V285.484L570.88 288.306L565.995 285.49Z"/>
<path id="land_874" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 297.034V291.402L575.177 289.985L577.62 288.58L580.062 289.985L582.505 291.402V297.028L577.62 299.85L572.729 297.034Z"/>
<path id="land_875" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 308.572V302.94L584.359 300.124L589.244 302.94V308.572L584.359 311.394L579.468 308.572Z"/>
<path id="land_876" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 320.122V314.484L591.093 311.663L595.984 314.484V320.116L591.093 322.932L586.208 320.122Z"/>
<path id="land_877" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 331.66V326.028L597.833 323.207L602.718 326.028V331.66L597.833 334.482L592.947 331.66Z"/>
<path id="land_878" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 343.205V337.567L604.572 334.751L609.457 337.567V343.205L604.572 346.021L599.681 343.205Z"/>
<path id="land_879" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 354.743V349.116L611.311 346.295L616.197 349.116V354.743L611.311 357.564L606.421 354.743Z"/>
<path id="land_880" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 366.287V360.66L618.045 357.839L622.936 360.66V366.287L618.045 369.109L613.16 366.287Z"/>
<path id="land_881" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 377.837V372.199L624.785 369.383L629.67 372.199V377.837L624.785 380.653L619.9 377.837Z"/>
<path id="land_882" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M431.234 31.5362V25.9042L436.12 23.0825L441.01 25.9098V31.5362L436.12 34.3523L431.234 31.5362Z"/>
<path id="land_883" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M437.974 43.086V37.4427L440.417 36.0375L442.859 34.6267L447.744 37.4483V43.0804L442.859 45.9021L437.974 43.086Z"/>
<path id="land_884" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 54.6246V48.9925L449.599 46.1709L454.484 48.9925V54.6246L449.599 57.4407L444.708 54.6246Z"/>
<path id="land_885" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 66.1632V60.5367L456.332 57.7151L461.223 60.5367V66.1632L456.332 68.9848L451.447 66.1632Z"/>
<path id="land_886" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 77.7071V72.0807L463.072 69.2534L467.962 72.0807V77.7071L463.072 80.5288L458.187 77.7071Z"/>
<path id="land_887" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 89.2513V83.6192L469.811 80.7976L474.696 83.6192V89.2513L469.811 92.073L464.926 89.2513Z"/>
<path id="land_888" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 100.795V95.1634L476.551 92.3418L481.436 95.1634V100.795L476.551 103.617L471.66 100.795Z"/>
<path id="land_889" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 112.334V106.707L483.285 103.886L488.175 106.707V112.334L483.285 115.161L478.399 112.334Z"/>
<path id="land_890" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 123.883V118.251L490.024 115.43L494.909 118.251V123.883L490.024 126.7L485.133 123.883Z"/>
<path id="land_891" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 135.422V129.796L496.763 126.974L501.648 129.79V135.428L496.763 138.244L491.878 135.422Z"/>
<path id="land_892" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 146.966V141.334L503.503 138.518L508.388 141.334V146.966L503.503 149.788L498.612 146.966Z"/>
<path id="land_893" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 158.51V152.884L510.242 150.062L515.128 152.878V158.51L510.242 161.332L505.352 158.51Z"/>
<path id="land_894" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 170.054V164.422L516.976 161.601L521.861 164.422V170.054L516.976 172.87L512.091 170.054Z"/>
<path id="land_895" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 181.599V175.966L523.715 173.145L528.6 175.966V181.599L523.715 184.42L518.83 181.599Z"/>
<path id="land_896" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 193.137V187.51L530.455 184.689L535.34 187.51V193.137L530.455 195.958L525.564 193.137Z"/>
<path id="land_897" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 204.681V199.054L537.189 196.233L542.08 199.054V204.686L537.189 207.503L532.298 204.681Z"/>
<path id="land_898" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 216.225V210.599L543.928 207.777L548.813 210.599V216.225L543.928 219.047L539.038 216.225Z"/>
<path id="land_899" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 227.775V222.137L550.668 219.315L555.553 222.143V227.769L550.668 230.591L545.782 227.775Z"/>
<path id="land_900" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 239.313V233.681L557.407 230.865L562.292 233.681V239.313L557.407 242.141L552.516 239.313Z"/>
<path id="land_901" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 250.857V245.225L564.141 242.404L569.032 245.225V250.857L564.141 253.673L559.256 250.857Z"/>
<path id="land_902" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 262.402V256.764L570.88 253.948L575.766 256.769V262.396L570.88 265.218L565.995 262.402Z"/>
<path id="land_903" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 273.945V268.313L577.62 265.492L582.505 268.313V273.945L577.62 276.761L572.729 273.945Z"/>
<path id="land_904" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 285.49V279.852L584.359 277.036L589.244 279.852V285.49L584.359 288.306L579.468 285.49Z"/>
<path id="land_905" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 297.034V291.402L591.093 288.58L595.984 291.402V297.028L591.093 299.85L586.208 297.034Z"/>
<path id="land_906" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 308.572V302.94L597.833 300.118L602.718 302.94V308.572L597.833 311.394L592.947 308.572Z"/>
<path id="land_907" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 320.122V314.484L604.572 311.663L609.457 314.484V320.122L604.572 322.932L599.681 320.122Z"/>
<path id="land_908" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 331.655V326.028L611.311 323.207L616.197 326.028V331.655L611.311 334.482L606.421 331.655Z"/>
<path id="land_909" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 343.205V337.567L618.045 334.751L622.936 337.567V343.205L618.045 346.021L613.16 343.205Z"/>
<path id="land_910" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 354.748V349.111L624.785 346.295L629.67 349.111V354.748L627.227 356.154L624.785 357.564L619.9 354.748Z"/>
<path id="land_911" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 366.287V360.66L631.524 357.839L636.409 360.66V366.287L631.524 369.103L626.633 366.287Z"/>
<path id="land_912" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 377.831V372.199L638.264 369.383L643.143 372.199V377.837L638.258 380.653L633.373 377.831Z"/>
<path id="land_913" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 470.178V464.552L692.168 461.73L697.053 464.552V470.178L692.168 473L687.272 470.178Z"/>
<path id="land_914" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M444.708 31.5362V25.9042L449.599 23.0825L454.484 25.9042V31.5362L449.599 34.3523L444.708 31.5362Z"/>
<path id="land_915" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M451.447 43.0804V37.4483L456.332 34.6267L461.223 37.4483V43.0804L456.332 45.8965L451.447 43.0804Z"/>
<path id="land_916" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 54.6247V48.9927L463.072 46.1654L467.962 48.9927V54.6247L463.072 57.4408L458.187 54.6247Z"/>
<path id="land_917" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 66.1688V60.5311L469.811 57.7151L474.696 60.5311V66.1688L469.811 68.9848L464.926 66.1688Z"/>
<path id="land_918" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 77.7071V72.0807L476.551 69.2534L481.436 72.0807V77.7071L476.551 80.5288L471.66 77.7071Z"/>
<path id="land_919" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 89.2513V83.6192L483.285 80.7976L488.175 83.6192V89.2513L483.285 92.073L478.399 89.2513Z"/>
<path id="land_920" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 100.795V95.1634L490.024 92.3418L494.909 95.1634V100.795L490.024 103.617L485.133 100.795Z"/>
<path id="land_921" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 112.334V106.707L496.763 103.886L501.648 106.707V112.339L496.763 115.161L491.878 112.334Z"/>
<path id="land_922" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 123.883V118.246L503.503 115.43L508.388 118.246V123.883L503.503 126.7L498.612 123.883Z"/>
<path id="land_923" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 135.422V129.796L510.242 126.974L515.128 129.796V135.428L510.242 138.244L505.352 135.422Z"/>
<path id="land_924" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 146.966V141.334L516.976 138.518L521.861 141.334V146.966L516.976 149.788L512.091 146.966Z"/>
<path id="land_925" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 158.51V152.878L523.715 150.062L528.6 152.878V158.51L523.715 161.332L518.83 158.51Z"/>
<path id="land_926" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 170.054V164.422L530.455 161.601L535.34 164.422V170.054L530.455 172.876L525.564 170.054Z"/>
<path id="land_927" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 181.593V175.966L537.189 173.145L542.08 175.966V181.599L537.189 184.42L532.298 181.593Z"/>
<path id="land_928" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 193.137V187.51L543.928 184.689L548.813 187.51V193.137L543.928 195.958L539.038 193.137Z"/>
<path id="land_929" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 204.686V199.049L550.668 196.233L555.553 199.054V204.686L550.668 207.503L545.782 204.686Z"/>
<path id="land_930" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 216.225V210.599L557.407 207.777L562.292 210.599V216.225L557.407 219.047L552.516 216.225Z"/>
<path id="land_931" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 227.769V222.143L564.141 219.321L569.032 222.143V227.769L564.141 230.591L559.256 227.769Z"/>
<path id="land_932" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 239.319V233.681L570.88 230.865L575.766 233.681V239.313L570.88 242.135L565.995 239.319Z"/>
<path id="land_933" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 250.857V245.225L575.177 243.809L577.62 242.409L580.062 243.814L582.505 245.225V250.857L577.62 253.673L572.729 250.857Z"/>
<path id="land_934" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 262.402V256.769L584.359 253.948L589.244 256.769V262.402L584.359 265.218L579.468 262.402Z"/>
<path id="land_935" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 273.945V268.313L591.093 265.492L595.984 268.313V273.945L591.093 276.761L586.208 273.945Z"/>
<path id="land_936" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 285.49V279.852L597.833 277.03L602.718 279.852V285.49L597.833 288.306L592.947 285.49Z"/>
<path id="land_937" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 297.034V291.396L604.572 288.58L609.457 291.402V297.034L604.572 299.85L599.681 297.034Z"/>
<path id="land_938" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 308.572V302.94L611.311 300.118L616.197 302.946V308.572L611.311 311.394L606.421 308.572Z"/>
<path id="land_939" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 320.122V314.484L618.045 311.663L622.936 314.484V320.116L618.045 322.932L613.16 320.122Z"/>
<path id="land_940" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 331.66V326.028L624.785 323.207L629.67 326.028V331.66L624.785 334.482L619.9 331.66Z"/>
<path id="land_941" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 343.205V337.567L631.524 334.751L636.409 337.567V343.205L631.524 346.021L626.633 343.205Z"/>
<path id="land_942" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 354.743V349.116L638.264 346.295L643.143 349.111V354.743L638.258 357.564L633.373 354.743Z"/>
<path id="land_943" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 366.287V360.66L644.998 357.839L649.883 360.655V366.293L644.998 369.109L640.112 366.287Z"/>
<path id="land_944" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 377.837V372.199L651.737 369.383L656.622 372.199V377.837L651.737 380.653L646.852 377.837Z"/>
<path id="land_945" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 389.375V383.743L658.476 380.921L663.361 383.743V389.375L658.476 392.197L653.586 389.375Z"/>
<path id="land_946" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 400.919V395.287L665.216 392.471L670.101 395.287V400.919L665.216 403.741L660.325 400.919Z"/>
<path id="land_947" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 424.008V418.37L678.689 415.554L683.574 418.37V424.008L678.689 426.824L673.804 424.008Z"/>
<path id="land_948" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 458.634V453.008L698.902 450.186L703.793 453.008V458.634L698.902 461.456L694.017 458.634Z"/>
<path id="land_949" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M458.187 31.5362V25.9098L463.072 23.0825L467.962 25.9098V31.5362L463.072 34.3523L458.187 31.5362Z"/>
<path id="land_950" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M464.926 43.086V37.4427L469.811 34.6267L474.696 37.4483V43.0804L469.811 45.9021L464.926 43.086Z"/>
<path id="land_951" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 54.6246V48.9925L476.551 46.1709L481.436 48.9925V54.6246L476.551 57.4407L471.66 54.6246Z"/>
<path id="land_952" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 66.1632V60.5367L483.285 57.7151L488.175 60.5367V66.1632L483.285 68.9848L478.399 66.1632Z"/>
<path id="land_953" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 77.7071V72.0807L490.024 69.2534L494.909 72.0807V77.7071L490.024 80.5288L485.133 77.7071Z"/>
<path id="land_954" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 89.2513V83.6192L496.763 80.7976L501.648 83.6192V89.2513L496.763 92.073L491.878 89.2513Z"/>
<path id="land_955" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 100.795V95.1634L503.503 92.3418L508.388 95.1634V100.795L503.503 103.617L498.612 100.795Z"/>
<path id="land_956" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 112.334V106.707L510.242 103.886L515.128 106.707V112.339L510.242 115.161L505.352 112.334Z"/>
<path id="land_957" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 123.883V118.251L516.976 115.43L521.861 118.246V123.883L516.976 126.7L512.091 123.883Z"/>
<path id="land_958" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 135.428V129.796L523.715 126.974L528.6 129.79V135.428L523.715 138.244L518.83 135.428Z"/>
<path id="land_959" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 146.966V141.334L530.455 138.518L535.34 141.334V146.966L530.455 149.788L525.564 146.966Z"/>
<path id="land_960" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 158.51V152.884L537.189 150.062L542.08 152.884V158.51L537.189 161.332L532.298 158.51Z"/>
<path id="land_961" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 170.054V164.422L543.928 161.601L548.813 164.422V170.054L543.928 172.87L539.038 170.054Z"/>
<path id="land_962" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 181.599V175.966L550.668 173.145L555.553 175.966V181.599L550.668 184.42L545.782 181.599Z"/>
<path id="land_963" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 193.137V187.51L554.965 186.099L557.407 184.689L562.292 187.51V193.137L557.407 195.958L552.516 193.137Z"/>
<path id="land_964" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 204.686V199.054L564.141 196.233L569.032 199.054V204.686L564.141 207.503L559.256 204.686Z"/>
<path id="land_965" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 216.231V210.593L570.88 207.777L575.766 210.599V216.225L570.88 219.047L565.995 216.231Z"/>
<path id="land_966" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 227.775V222.137L577.62 219.321L580.062 220.726L582.505 222.143V227.769L577.62 230.591L572.729 227.775Z"/>
<path id="land_967" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 239.313V233.681L584.359 230.865L589.244 233.681V239.313L584.359 242.135L579.468 239.313Z"/>
<path id="land_968" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 250.857V245.225L591.093 242.404L595.984 245.225V250.857L591.093 253.673L586.208 250.857Z"/>
<path id="land_969" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 262.402V256.769L597.833 253.948L602.718 256.769V262.402L597.833 265.223L592.947 262.402Z"/>
<path id="land_970" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 273.945V268.313L604.572 265.492L609.457 268.313V273.945L604.572 276.761L599.681 273.945Z"/>
<path id="land_971" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 285.49V279.852L611.311 277.03L616.197 279.858V285.484L611.311 288.306L606.421 285.49Z"/>
<path id="land_972" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 297.034V291.402L618.045 288.58L622.936 291.402V297.034L618.045 299.85L613.16 297.034Z"/>
<path id="land_973" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 308.572V302.94L622.342 301.535L624.785 300.118L629.67 302.94V308.572L624.785 311.394L619.9 308.572Z"/>
<path id="land_974" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 320.116V314.484L631.524 311.668L636.409 314.484V320.116L631.524 322.932L626.633 320.116Z"/>
<path id="land_975" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 331.66V326.028L638.264 323.207L643.143 326.028V331.66L638.258 334.482L633.373 331.66Z"/>
<path id="land_976" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 343.205V337.567L644.998 334.751L649.883 337.567V343.205L644.998 346.021L640.112 343.205Z"/>
<path id="land_977" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 354.743V349.111L651.737 346.295L656.622 349.111V354.748L651.737 357.564L646.852 354.743Z"/>
<path id="land_978" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 366.287V360.66L658.476 357.839L663.361 360.655V366.293L658.476 369.109L653.586 366.287Z"/>
<path id="land_979" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 377.831V372.205L665.216 369.383L670.101 372.199V377.831L665.216 380.653L660.325 377.831Z"/>
<path id="land_980" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 389.375V383.749L671.95 380.921L676.84 383.749V389.375L671.95 392.197L667.064 389.375Z"/>
<path id="land_981" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 400.919V395.287L678.689 392.471L683.574 395.287V400.925L678.689 403.741L673.804 400.919Z"/>
<path id="land_982" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 412.463V406.831L685.428 404.01L690.314 406.831V412.463L685.428 415.285L680.538 412.463Z"/>
<path id="land_983" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 424.002V418.376L692.168 415.554L697.053 418.376V424.002L692.168 426.824L687.272 424.002Z"/>
<path id="land_984" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 435.546V429.92L698.902 427.092L703.793 429.92V435.546L698.902 438.368L694.017 435.546Z"/>
<path id="land_985" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 447.096V441.458L705.641 438.642L710.526 441.458V447.096L705.641 449.912L700.756 447.096Z"/>
<path id="land_986" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M471.66 31.5362V25.9098L476.551 23.0825L481.436 25.9098V31.5362L476.551 34.3523L471.66 31.5362Z"/>
<path id="land_987" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 43.0804V37.4483L483.285 34.6267L488.175 37.4483V43.0804L483.285 45.9021L478.399 43.0804Z"/>
<path id="land_988" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 54.6246V48.9925L490.024 46.1709L494.909 48.9925V54.6246L490.024 57.4407L485.133 54.6246Z"/>
<path id="land_989" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 66.1632V60.5367L496.763 57.7151L501.648 60.5367V66.1688L496.763 68.9848L491.878 66.1632Z"/>
<path id="land_990" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 77.7071V72.0807L503.503 69.2534L508.388 72.0807V77.7071L503.503 80.5288L498.612 77.7071Z"/>
<path id="land_991" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 89.2513V83.6192L510.242 80.7976L515.128 83.6192V89.2513L510.242 92.073L505.352 89.2513Z"/>
<path id="land_992" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 100.795V95.1634L516.976 92.3418L521.861 95.1634V100.795L516.976 103.617L512.091 100.795Z"/>
<path id="land_993" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 112.339V106.707L523.715 103.886L528.6 106.707V112.339L523.715 115.161L518.83 112.339Z"/>
<path id="land_994" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 123.883V118.251L530.455 115.43L535.34 118.246V123.883L530.455 126.7L525.564 123.883Z"/>
<path id="land_995" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 135.422V129.796L537.189 126.974L542.08 129.796V135.422L537.189 138.244L532.298 135.422Z"/>
<path id="land_996" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 146.966V141.334L543.928 138.518L548.813 141.334V146.966L543.928 149.788L539.038 146.966Z"/>
<path id="land_997" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 158.516V152.878L550.668 150.062L555.553 152.884V158.51L553.11 159.927L550.668 161.332L545.782 158.516Z"/>
<path id="land_998" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 170.054V164.422L557.407 161.601L562.292 164.422V170.054L557.407 172.876L552.516 170.054Z"/>
<path id="land_999" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 181.599V175.966L564.141 173.145L569.032 175.966V181.599L564.141 184.42L559.256 181.599Z"/>
<path id="land_1000" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 193.142V187.505L570.88 184.689L575.766 187.51V193.137L570.88 195.958L565.995 193.142Z"/>
<path id="land_1001" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 204.686V199.054L577.62 196.233L582.505 199.054V204.686L577.62 207.503L572.729 204.686Z"/>
<path id="land_1002" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 216.225V210.593L584.359 207.777L589.244 210.593V216.225L584.359 219.047L579.468 216.225Z"/>
<path id="land_1003" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 227.775V222.137L591.093 219.321L595.984 222.143V227.769L591.093 230.591L586.208 227.775Z"/>
<path id="land_1004" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 239.313V233.681L597.833 230.865L602.718 233.681V239.313L597.833 242.141L592.947 239.313Z"/>
<path id="land_1005" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 250.857V245.225L604.572 242.409L609.457 245.225V250.857L604.572 253.673L599.681 250.857Z"/>
<path id="land_1006" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 262.402V256.769L611.311 253.948L616.197 256.769V262.396L611.311 265.218L606.421 262.402Z"/>
<path id="land_1007" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 273.945V268.313L618.045 265.492L622.936 268.313V273.945L618.045 276.761L613.16 273.945Z"/>
<path id="land_1008" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 285.49V279.852L624.785 277.036L629.67 279.852V285.49L624.785 288.306L619.9 285.49Z"/>
<path id="land_1009" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 297.028V291.402L631.524 288.58L633.967 289.985L636.409 291.402V297.028L631.524 299.85L626.633 297.028Z"/>
<path id="land_1010" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 308.572V302.94L638.264 300.118L643.143 302.94V308.572L638.258 311.394L633.373 308.572Z"/>
<path id="land_1011" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 320.116V314.484L644.998 311.663L649.883 314.484V320.122L644.998 322.932L640.112 320.116Z"/>
<path id="land_1012" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 331.66V326.028L651.737 323.207L656.622 326.028V331.66L651.737 334.482L646.852 331.66Z"/>
<path id="land_1013" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 343.205V337.567L658.476 334.751L663.361 337.567V343.205L658.476 346.021L653.586 343.205Z"/>
<path id="land_1014" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 354.743V349.116L665.216 346.295L670.101 349.116V354.743L665.216 357.564L660.325 354.743Z"/>
<path id="land_1015" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 366.287V360.66L671.95 357.839L676.84 360.66V366.287L671.95 369.109L667.064 366.287Z"/>
<path id="land_1016" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 377.837V372.199L678.689 369.383L683.574 372.199V377.837L678.689 380.653L673.804 377.837Z"/>
<path id="land_1017" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 389.375V383.749L685.428 380.921L690.314 383.743V389.375L685.428 392.197L680.538 389.375Z"/>
<path id="land_1018" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 400.919V395.293L692.168 392.471L697.053 395.293V400.919L692.168 403.741L687.272 400.919Z"/>
<path id="land_1019" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 412.463V406.831L698.902 404.01L703.793 406.831V412.458L698.902 415.285L694.017 412.463Z"/>
<path id="land_1020" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 424.008V418.37L705.641 415.554L710.526 418.37V424.008L705.641 426.824L700.756 424.008Z"/>
<path id="land_1021" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 435.546V429.92L712.381 427.092L717.266 429.92V435.546L712.381 438.368L707.49 435.546Z"/>
<path id="land_1022" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 447.09V441.458L719.114 438.642L724.005 441.458V447.09L719.114 449.912L714.229 447.09Z"/>
<path id="land_1023" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 458.634V453.008L725.854 450.186L730.745 453.008V458.634L725.854 461.456L720.963 458.634Z"/>
<path id="land_1024" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 470.178V464.546L732.594 461.73L737.479 464.546V470.178L732.594 473L727.703 470.178Z"/>
<path id="land_1025" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M478.399 19.9923V14.3602L483.285 11.5442L488.175 14.3602V19.9923L483.285 22.8139L478.399 19.9923Z"/>
<path id="land_1026" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M485.133 31.5362V25.9098L490.024 23.0825L494.909 25.9098V31.5362L490.024 34.3523L485.133 31.5362Z"/>
<path id="land_1027" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 43.0804V37.4483L496.763 34.6267L501.648 37.4483V43.0804L496.763 45.9021L491.878 43.0804Z"/>
<path id="land_1028" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 54.6246V48.9925L503.503 46.1709L508.388 48.9925V54.6246L503.503 57.4407L498.612 54.6246Z"/>
<path id="land_1029" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 66.1632V60.5367L510.242 57.7151L515.128 60.5367V66.1632L510.242 68.9848L505.352 66.1632Z"/>
<path id="land_1030" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 77.7071V72.0807L516.976 69.2534L521.861 72.0807V77.7127L516.976 80.5288L512.091 77.7071Z"/>
<path id="land_1031" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 89.2513V83.6192L523.715 80.7976L528.6 83.6192V89.2513L523.715 92.073L518.83 89.2513Z"/>
<path id="land_1032" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 100.795V95.1634L530.455 92.3418L535.34 95.1634V100.795L530.455 103.617L525.564 100.795Z"/>
<path id="land_1033" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 112.334V106.707L537.189 103.886L542.08 106.707V112.339L537.189 115.161L532.298 112.334Z"/>
<path id="land_1034" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 123.883V118.251L543.928 115.43L548.813 118.251V123.883L543.928 126.7L539.038 123.883Z"/>
<path id="land_1035" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 135.428V129.79L550.668 126.974L555.553 129.796V135.422L550.668 138.244L545.782 135.428Z"/>
<path id="land_1036" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 146.966V141.334L557.407 138.518L562.292 141.334V146.966L557.407 149.788L552.516 146.966Z"/>
<path id="land_1037" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 158.51V152.884L564.141 150.062L569.032 152.884V158.51L564.141 161.332L559.256 158.51Z"/>
<path id="land_1038" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 170.054V164.422L570.88 161.601L575.766 164.422V170.054L570.88 172.87L565.995 170.054Z"/>
<path id="land_1039" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 181.598V175.966L577.62 173.15L582.505 175.966V181.598L577.62 184.42L572.729 181.598Z"/>
<path id="land_1040" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 193.142V187.51L584.359 184.689L589.244 187.51V193.137L584.359 195.958L579.468 193.142Z"/>
<path id="land_1041" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 204.686V199.054L591.093 196.233L595.984 199.054V204.686L591.093 207.503L586.208 204.686Z"/>
<path id="land_1042" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 216.225V210.593L597.833 207.777L602.718 210.593V216.225L597.833 219.047L592.947 216.225Z"/>
<path id="land_1043" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 227.775V222.137L604.572 219.321L609.457 222.137V227.769L604.572 230.591L599.681 227.775Z"/>
<path id="land_1044" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 239.313V233.681L611.311 230.865L616.197 233.687V239.313L611.311 242.141L606.421 239.313Z"/>
<path id="land_1045" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 250.857V245.225L618.045 242.404L622.936 245.225V250.857L618.045 253.673L613.16 250.857Z"/>
<path id="land_1046" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 262.402V256.764L622.342 255.359L624.785 253.948L627.222 255.359L629.67 256.764V262.402L624.785 265.218L619.9 262.402Z"/>
<path id="land_1047" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 273.945V268.313L631.524 265.492L636.409 268.313V273.945L631.524 276.761L626.633 273.945Z"/>
<path id="land_1048" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 285.49V279.852L638.264 277.036L643.143 279.852V285.49L638.258 288.306L633.373 285.49Z"/>
<path id="land_1049" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 297.028V291.402L644.998 288.58L649.883 291.396V297.034L644.998 299.85L640.112 297.028Z"/>
<path id="land_1050" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 308.572V302.94L651.737 300.124L656.622 302.94V308.572L651.737 311.394L646.852 308.572Z"/>
<path id="land_1051" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 320.122V314.484L658.476 311.663L663.361 314.484V320.122L658.476 322.932L653.586 320.122Z"/>
<path id="land_1052" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 331.655V326.028L665.216 323.207L670.101 326.028V331.655L665.216 334.482L660.325 331.655Z"/>
<path id="land_1053" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 343.205V337.567L671.95 334.751L676.84 337.567V343.205L671.95 346.021L667.064 343.205Z"/>
<path id="land_1054" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 354.743V349.111L678.689 346.295L683.574 349.111V354.748L678.689 357.564L673.804 354.743Z"/>
<path id="land_1055" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 366.287V360.66L685.428 357.839L690.314 360.655V366.293L685.428 369.109L680.538 366.287Z"/>
<path id="land_1056" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 377.831V372.205L692.168 369.383L697.053 372.205V377.831L692.168 380.653L687.272 377.831Z"/>
<path id="land_1057" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 389.375V383.749L698.902 380.921L703.793 383.749V389.375L698.902 392.197L694.017 389.375Z"/>
<path id="land_1058" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 400.925V395.287L705.641 392.471L710.526 395.287V400.919L705.641 403.741L700.756 400.925Z"/>
<path id="land_1059" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 412.463V406.831L712.381 404.01L717.266 406.831V412.463L712.381 415.285L707.49 412.463Z"/>
<path id="land_1060" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 424.008V418.37L719.114 415.554L724.005 418.37V424.008L719.114 426.824L714.229 424.008Z"/>
<path id="land_1061" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 435.546V429.92L725.854 427.092L730.745 429.92V435.546L725.854 438.368L720.963 435.546Z"/>
<path id="land_1062" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 447.096V441.458L732.594 438.642L737.479 441.458V447.09L732.594 449.912L727.703 447.096Z"/>
<path id="land_1063" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 458.634V453.002L739.327 450.186L744.218 453.002V458.634L739.327 461.456L734.442 458.634Z"/>
<path id="land_1064" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 470.178V464.546L746.067 461.73L750.957 464.546V470.178L746.067 473L741.181 470.178Z"/>
<path id="land_1065" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M491.878 19.9923V14.3602L496.763 11.5442L501.648 14.3602V19.9979L496.763 22.8139L491.878 19.9923Z"/>
<path id="land_1066" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 31.5362V25.9042L503.503 23.0825L508.388 25.9042V31.5362L503.503 34.3523L498.612 31.5362Z"/>
<path id="land_1067" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 43.0804V37.4483L510.242 34.6267L515.128 37.4483V43.0804L510.242 45.9021L505.352 43.0804Z"/>
<path id="land_1068" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 54.6246V48.9925L516.976 46.1709L521.861 48.9925V54.6246L516.976 57.4407L512.091 54.6246Z"/>
<path id="land_1069" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 66.1632V60.5367L523.715 57.7151L528.6 60.5367V66.1688L523.715 68.9848L518.83 66.1632Z"/>
<path id="land_1070" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 77.7071V72.0807L530.455 69.2534L535.34 72.0807V77.7127L530.455 80.5288L525.564 77.7071Z"/>
<path id="land_1071" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 89.2457V83.6248L537.189 80.7976L542.08 83.6192V89.2513L537.189 92.073L532.298 89.2457Z"/>
<path id="land_1072" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 100.795V95.1634L543.928 92.3418L548.813 95.1634V100.795L543.928 103.617L539.038 100.795Z"/>
<path id="land_1073" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 112.339V106.702L550.668 103.886L555.553 106.707V112.339L550.668 115.161L545.782 112.339Z"/>
<path id="land_1074" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 123.883V118.251L557.407 115.43L562.292 118.251V123.883L557.407 126.7L552.516 123.883Z"/>
<path id="land_1075" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 135.422V129.796L564.141 126.974L569.032 129.796V135.422L564.141 138.244L559.256 135.422Z"/>
<path id="land_1076" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 146.966V141.334L570.88 138.518L575.766 141.334V146.966L570.88 149.788L565.995 146.966Z"/>
<path id="land_1077" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M575.172 159.927L572.729 158.51V152.878L577.62 150.062L582.505 152.884V158.51L580.062 159.927L577.62 161.332L575.172 159.927Z"/>
<path id="land_1078" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 170.054V164.422L584.359 161.601L589.244 164.422V170.054L584.359 172.87L579.468 170.054Z"/>
<path id="land_1079" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 181.599V175.966L591.093 173.145L595.984 175.966V181.599L591.093 184.42L586.208 181.599Z"/>
<path id="land_1080" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 193.142V187.505L597.833 184.689L602.718 187.51V193.137L597.833 195.958L592.947 193.142Z"/>
<path id="land_1081" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 204.686V199.054L604.572 196.233L609.457 199.054V204.686L604.572 207.503L599.681 204.686Z"/>
<path id="land_1082" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 216.225V210.599L611.311 207.777L616.197 210.599V216.225L611.311 219.047L606.421 216.225Z"/>
<path id="land_1083" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 227.769V222.143L618.045 219.321L622.936 222.143V227.769L618.045 230.591L613.16 227.769Z"/>
<path id="land_1084" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 239.313V233.681L624.785 230.865L629.67 233.681V239.313L624.785 242.141L619.9 239.313Z"/>
<path id="land_1085" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 250.857V245.225L629.082 243.809L631.524 242.409L633.967 243.814L636.409 245.225V250.857L631.524 253.673L626.633 250.857Z"/>
<path id="land_1086" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 262.402V256.769L638.258 253.948L643.143 256.769V262.402L638.258 265.218L633.373 262.402Z"/>
<path id="land_1087" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 273.945V268.313L644.998 265.492L649.883 268.313V273.945L644.998 276.761L640.112 273.945Z"/>
<path id="land_1088" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 285.49V279.852L651.737 277.036L656.622 279.852V285.49L651.737 288.306L646.852 285.49Z"/>
<path id="land_1089" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 297.034V291.402L658.476 288.58L663.361 291.396V297.034L658.476 299.85L653.586 297.034Z"/>
<path id="land_1090" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 308.572V302.946L665.216 300.118L670.101 302.94V308.572L665.216 311.394L660.325 308.572Z"/>
<path id="land_1091" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 320.116V314.484L671.95 311.663L676.84 314.484V320.122L671.95 322.938L667.064 320.116Z"/>
<path id="land_1092" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 331.66V326.028L678.689 323.207L683.574 326.023V331.66L678.689 334.482L673.804 331.66Z"/>
<path id="land_1093" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 343.205V337.567L685.428 334.751L690.314 337.567V343.205L685.428 346.021L680.538 343.205Z"/>
<path id="land_1094" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 354.743V349.116L692.168 346.295L697.053 349.116V354.743L692.168 357.564L687.272 354.743Z"/>
<path id="land_1095" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 366.287V360.66L698.902 357.839L703.793 360.66V366.287L698.902 369.109L694.017 366.287Z"/>
<path id="land_1096" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 377.837V372.199L705.641 369.383L710.526 372.199V377.837L705.641 380.653L700.756 377.837Z"/>
<path id="land_1097" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 389.375V383.749L712.381 380.921L717.266 383.743V389.375L712.381 392.197L707.49 389.375Z"/>
<path id="land_1098" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 400.919V395.287L719.114 392.471L724.005 395.287V400.919L719.114 403.741L714.229 400.919Z"/>
<path id="land_1099" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 412.463V406.831L725.854 404.01L730.745 406.831V412.458L725.854 415.285L720.963 412.463Z"/>
<path id="land_1100" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 424.008V418.37L732.594 415.554L737.479 418.37V424.008L732.594 426.824L727.703 424.008Z"/>
<path id="land_1101" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 435.546V429.914L739.327 427.098L744.218 429.919V435.546L739.327 438.367L734.442 435.546Z"/>
<path id="land_1102" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 447.09V441.458L746.067 438.642L750.957 441.458V447.09L746.067 449.912L741.181 447.09Z"/>
<path id="land_1103" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 458.634V453.008L752.806 450.186L757.691 453.008V458.634L752.806 461.456L747.921 458.634Z"/>
<path id="land_1104" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M498.612 8.44812V2.81604L503.503 0L508.388 2.81604V8.44812L503.503 11.2642L498.612 8.44812Z"/>
<path id="land_1105" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M505.352 19.9923V14.3602L510.242 11.5442L515.128 14.3602V19.9979L510.242 22.8139L505.352 19.9923Z"/>
<path id="land_1106" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 31.5362V25.9098L516.976 23.0825L521.861 25.9042V31.5362L516.976 34.3523L512.091 31.5362Z"/>
<path id="land_1107" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 43.0804V37.4483L523.715 34.6267L528.6 37.4483V43.0804L523.715 45.9021L518.83 43.0804Z"/>
<path id="land_1108" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 54.6247V48.9927L530.455 46.1654L535.34 48.9927V54.6247L530.455 57.4408L525.564 54.6247Z"/>
<path id="land_1109" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 66.1632V60.5367L537.189 57.7151L542.08 60.5367V66.1632L537.189 68.9848L532.298 66.1632Z"/>
<path id="land_1110" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 77.7071V72.0807L543.928 69.2534L548.813 72.0807V77.7071L543.928 80.5288L539.038 77.7071Z"/>
<path id="land_1111" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 89.2513V83.6192L550.668 80.7976L555.553 83.6192V89.2513L550.668 92.073L545.782 89.2513Z"/>
<path id="land_1112" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 100.795V95.1634L557.407 92.3418L562.292 95.1634V100.795L557.407 103.617L552.516 100.795Z"/>
<path id="land_1113" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 112.339V106.707L564.141 103.886L569.032 106.707V112.334L564.141 115.161L559.256 112.339Z"/>
<path id="land_1114" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 123.883V118.246L570.88 115.43L575.766 118.251V123.883L570.88 126.7L565.995 123.883Z"/>
<path id="land_1115" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 135.428V129.79L577.62 126.974L582.505 129.796V135.422L577.62 138.244L572.729 135.428Z"/>
<path id="land_1116" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 146.966V141.334L584.359 138.518L589.244 141.334V146.966L584.359 149.788L579.468 146.966Z"/>
<path id="land_1117" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 158.51V152.878L591.093 150.062L595.984 152.884V158.51L591.093 161.332L586.208 158.51Z"/>
<path id="land_1118" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 170.054V164.422L597.833 161.601L602.718 164.422V170.054L597.833 172.876L592.947 170.054Z"/>
<path id="land_1119" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 181.599V175.966L604.572 173.145L609.457 175.966V181.599L604.572 184.42L599.681 181.599Z"/>
<path id="land_1120" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 193.137V187.51L611.311 184.689L616.197 187.51V193.137L611.311 195.958L606.421 193.137Z"/>
<path id="land_1121" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 204.686V199.054L618.045 196.233L622.936 199.054V204.686L618.045 207.503L613.16 204.686Z"/>
<path id="land_1122" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 216.231V210.593L624.785 207.777L629.67 210.593V216.231L624.785 219.047L619.9 216.231Z"/>
<path id="land_1123" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 227.769V222.143L631.524 219.321L636.409 222.143V227.769L631.524 230.591L626.633 227.769Z"/>
<path id="land_1124" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 239.313V233.681L638.264 230.865L643.143 233.681V239.313L638.258 242.141L633.373 239.313Z"/>
<path id="land_1125" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 250.857V245.225L644.998 242.404L649.883 245.225V250.857L644.998 253.673L640.112 250.857Z"/>
<path id="land_1126" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 262.402V256.769L651.737 253.948L656.622 256.769V262.402L651.737 265.218L646.852 262.402Z"/>
<path id="land_1127" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 273.945V268.313L658.476 265.492L663.361 268.313V273.945L658.476 276.761L653.586 273.945Z"/>
<path id="land_1128" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 285.484V279.857L665.216 277.036L670.101 279.852V285.49L665.216 288.306L660.325 285.484Z"/>
<path id="land_1129" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 297.028V291.402L671.95 288.58L674.398 289.985L676.84 291.402V297.034L671.95 299.85L667.064 297.028Z"/>
<path id="land_1130" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 308.572V302.94L678.689 300.118L683.574 302.94V308.572L678.689 311.394L673.804 308.572Z"/>
<path id="land_1131" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 320.122V314.484L685.428 311.663L690.314 314.484V320.122L685.428 322.938L680.538 320.122Z"/>
<path id="land_1132" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 331.655V326.028L692.168 323.207L697.053 326.028V331.655L692.168 334.482L687.272 331.655Z"/>
<path id="land_1133" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 343.205V337.567L698.902 334.751L703.793 337.573V343.205L698.902 346.021L694.017 343.205Z"/>
<path id="land_1134" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 354.748V349.111L705.641 346.295L710.526 349.111V354.748L705.641 357.564L700.756 354.748Z"/>
<path id="land_1135" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 366.287V360.66L712.381 357.839L717.266 360.655V366.293L712.381 369.109L707.49 366.287Z"/>
<path id="land_1136" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 377.831V372.199L719.114 369.383L724.005 372.205V377.831L719.114 380.653L714.229 377.831Z"/>
<path id="land_1137" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 389.375V383.749L725.854 380.921L730.745 383.749V389.37L725.854 392.197L720.963 389.375Z"/>
<path id="land_1138" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 400.919V395.287L732.594 392.471L737.479 395.287V400.919L732.594 403.741L727.703 400.919Z"/>
<path id="land_1139" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 412.463V406.831L739.327 404.01L744.218 406.831V412.463L739.327 415.285L734.442 412.463Z"/>
<path id="land_1140" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 424.008V418.37L746.067 415.554L750.957 418.37V424.008L746.067 426.824L741.181 424.008Z"/>
<path id="land_1141" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 435.546V429.919L752.806 427.098L757.691 429.919V435.546L752.806 438.367L747.921 435.546Z"/>
<path id="land_1142" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 447.09V441.458L759.546 438.642L764.431 441.458V447.096L759.546 449.912L754.66 447.09Z"/>
<path id="land_1143" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M512.091 8.44812V2.82164L516.976 0L521.861 2.81604V8.44812L516.976 11.2642L512.091 8.44812Z"/>
<path id="land_1144" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M518.83 19.9979V14.3602L523.715 11.5442L528.6 14.3602V19.9979L523.715 22.8139L518.83 19.9979Z"/>
<path id="land_1145" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 31.5362V25.9098L530.455 23.0825L535.34 25.9042V31.5362L530.455 34.3523L525.564 31.5362Z"/>
<path id="land_1146" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 43.0804V37.4483L537.189 34.6267L542.08 37.4483V43.0804L537.189 45.9021L532.298 43.0804Z"/>
<path id="land_1147" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 54.6246V48.9925L543.928 46.1709L548.813 48.9925V54.6246L543.928 57.4407L539.038 54.6246Z"/>
<path id="land_1148" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 66.1688V60.5311L550.668 57.7151L555.553 60.5367V66.1632L550.668 68.9848L545.782 66.1688Z"/>
<path id="land_1149" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 77.7071V72.0807L557.407 69.2534L562.292 72.0807V77.7071L557.407 80.5288L552.516 77.7071Z"/>
<path id="land_1150" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 89.2513V83.6192L564.141 80.7976L569.032 83.6192V89.2513L564.141 92.073L559.256 89.2513Z"/>
<path id="land_1151" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 100.801V95.1578L570.88 92.3418L575.766 95.1634V100.795L570.88 103.617L565.995 100.801Z"/>
<path id="land_1152" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 112.339V106.707L577.62 103.886L582.505 106.707V112.334L580.062 113.75L577.62 115.155L572.729 112.339Z"/>
<path id="land_1153" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 123.883V118.246L584.359 115.43L589.244 118.246V123.883L584.359 126.7L579.468 123.883Z"/>
<path id="land_1154" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 135.428V129.79L591.093 126.974L595.984 129.796V135.422L591.093 138.244L586.208 135.428Z"/>
<path id="land_1155" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 146.966V141.334L597.833 138.518L602.718 141.334V146.966L597.833 149.788L592.947 146.966Z"/>
<path id="land_1156" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 158.51V152.878L604.572 150.062L609.457 152.878V158.51L604.572 161.332L599.681 158.51Z"/>
<path id="land_1157" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 170.054V164.422L611.311 161.601L616.197 164.422V170.049L611.311 172.876L606.421 170.054Z"/>
<path id="land_1158" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 181.599V175.966L618.045 173.145L622.936 175.966V181.599L618.045 184.42L613.16 181.599Z"/>
<path id="land_1159" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 193.142V187.505L622.342 186.099L624.785 184.689L627.222 186.099L629.67 187.505V193.142L624.785 195.958L619.9 193.142Z"/>
<path id="land_1160" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 204.686V199.054L631.524 196.233L636.409 199.054V204.686L631.524 207.503L626.633 204.686Z"/>
<path id="land_1161" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 216.225V210.593L638.258 207.777L643.143 210.593V216.225L638.258 219.047L633.373 216.225Z"/>
<path id="land_1162" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 227.769V222.143L644.998 219.321L649.883 222.137V227.775L644.998 230.591L640.112 227.769Z"/>
<path id="land_1163" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 239.313V233.681L651.737 230.865L656.622 233.681V239.313L651.737 242.135L646.852 239.313Z"/>
<path id="land_1164" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 250.857V245.225L658.476 242.404L663.361 245.225V250.857L658.476 253.673L653.586 250.857Z"/>
<path id="land_1165" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 262.396V256.769L665.216 253.948L670.101 256.769V262.402L665.216 265.218L660.325 262.396Z"/>
<path id="land_1166" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 273.945V268.313L671.95 265.492L676.84 268.313V273.945L671.95 276.761L667.064 273.945Z"/>
<path id="land_1167" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 285.49V279.852L678.689 277.03L683.574 279.852V285.49L678.689 288.306L673.804 285.49Z"/>
<path id="land_1168" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 297.034V291.402L685.428 288.58L690.314 291.396V297.034L685.428 299.85L680.538 297.034Z"/>
<path id="land_1169" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 308.567V302.946L692.168 300.124L697.053 302.946V308.567L692.168 311.394L687.272 308.567Z"/>
<path id="land_1170" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 320.122V314.484L698.902 311.663L703.793 314.49V320.116L698.902 322.938L694.017 320.122Z"/>
<path id="land_1171" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 331.66V326.023L705.641 323.207L710.526 326.028V331.66L705.641 334.482L700.756 331.66Z"/>
<path id="land_1172" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 343.205V337.567L712.381 334.751L717.266 337.567V343.205L712.381 346.021L707.49 343.205Z"/>
<path id="land_1173" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 354.743V349.116L719.114 346.295L724.005 349.116V354.743L719.114 357.564L714.229 354.743Z"/>
<path id="land_1174" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 366.287V360.66L725.854 357.839L730.745 360.66V366.287L725.854 369.109L720.963 366.287Z"/>
<path id="land_1175" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 377.837V372.199L732.594 369.383L737.479 372.199V377.837L732.594 380.653L727.703 377.837Z"/>
<path id="land_1176" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 389.375V383.743L739.327 380.921L744.218 383.743V389.375L739.327 392.197L734.442 389.375Z"/>
<path id="land_1177" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 424.008V418.37L759.546 415.554L764.431 418.37V424.008L759.546 426.824L754.66 424.008Z"/>
<path id="land_1178" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 435.546V429.919L766.285 427.098L771.17 429.919V435.546L766.285 438.367L761.394 435.546Z"/>
<path id="land_1179" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M525.564 8.44812V2.82164L530.455 0L535.34 2.81604V8.44812L530.455 11.2642L525.564 8.44812Z"/>
<path id="land_1180" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M532.298 19.9923V14.3658L537.189 11.5442L542.08 14.3602V19.9979L537.189 22.8139L532.298 19.9923Z"/>
<path id="land_1181" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M539.038 31.5362V25.9098L543.928 23.0825L548.813 25.9098V31.5362L543.928 34.3523L539.038 31.5362Z"/>
<path id="land_1182" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 43.086V37.4427L550.668 34.6267L555.553 37.4483V43.0804L550.668 45.9021L545.782 43.086Z"/>
<path id="land_1183" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 54.6246V48.9925L557.407 46.1709L562.292 48.9925V54.6246L557.407 57.4407L552.516 54.6246Z"/>
<path id="land_1184" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 66.1632V60.5367L564.141 57.7151L569.032 60.5367V66.1632L564.141 68.9848L559.256 66.1632Z"/>
<path id="land_1185" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 77.7127V72.0751L570.88 69.2534L575.766 72.0807V77.7071L570.88 80.5288L565.995 77.7127Z"/>
<path id="land_1186" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 89.2512V83.6191L577.62 80.803L582.505 83.6191V89.2512L577.62 92.0728L572.729 89.2512Z"/>
<path id="land_1187" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 100.795V95.1634L584.359 92.3418L589.244 95.1634V100.795L584.359 103.617L579.468 100.795Z"/>
<path id="land_1188" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 112.339V106.707L591.093 103.886L595.984 106.707V112.334L591.093 115.161L586.208 112.339Z"/>
<path id="land_1189" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 123.883V118.246L597.833 115.43L602.718 118.246V123.883L597.833 126.7L592.947 123.883Z"/>
<path id="land_1190" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 135.428V129.79L604.572 126.974L609.457 129.796V135.428L604.572 138.244L599.681 135.428Z"/>
<path id="land_1191" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 146.966V141.334L611.311 138.518L616.197 141.34V146.966L611.311 149.788L606.421 146.966Z"/>
<path id="land_1192" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 158.51V152.884L618.045 150.062L622.936 152.884V158.51L618.045 161.332L613.16 158.51Z"/>
<path id="land_1193" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 170.054V164.422L624.785 161.601L629.67 164.422V170.054L624.785 172.87L619.9 170.054Z"/>
<path id="land_1194" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 181.598V175.966L631.524 173.15L636.409 175.966V181.598L631.524 184.42L626.633 181.598Z"/>
<path id="land_1195" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 193.137V187.51L638.264 184.689L643.143 187.51V193.142L638.258 195.958L633.373 193.137Z"/>
<path id="land_1196" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 204.686V199.054L644.998 196.233L649.883 199.049V204.686L644.998 207.503L640.112 204.686Z"/>
<path id="land_1197" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 216.225V210.593L651.737 207.777L656.622 210.593V216.225L651.737 219.047L646.852 216.225Z"/>
<path id="land_1198" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 227.769V222.137L658.476 219.321L663.361 222.137V227.775L658.476 230.591L653.586 227.769Z"/>
<path id="land_1199" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 239.313V233.687L665.216 230.865L670.101 233.681V239.313L665.216 242.141L660.325 239.313Z"/>
<path id="land_1200" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 250.857V245.225L671.95 242.404L676.84 245.225V250.857L671.95 253.673L667.064 250.857Z"/>
<path id="land_1201" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 262.402V256.769L678.689 253.948L681.126 255.359L683.574 256.764V262.402L678.689 265.218L673.804 262.402Z"/>
<path id="land_1202" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 273.945V268.313L685.428 265.492L690.314 268.308V273.945L685.428 276.761L680.538 273.945Z"/>
<path id="land_1203" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 285.484V279.857L692.168 277.036L697.053 279.857V285.484L692.168 288.306L687.272 285.484Z"/>
<path id="land_1204" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 297.034V291.402L698.902 288.58L703.793 291.402V297.028L698.902 299.85L694.017 297.034Z"/>
<path id="land_1205" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 308.572V302.94L705.641 300.118L710.526 302.94V308.572L705.641 311.394L700.756 308.572Z"/>
<path id="land_1206" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 320.116V314.484L712.381 311.663L717.266 314.484V320.122L712.381 322.938L707.49 320.116Z"/>
<path id="land_1207" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 331.655V326.028L719.114 323.207L724.005 326.028V331.655L719.114 334.482L714.229 331.655Z"/>
<path id="land_1208" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 343.205V337.567L725.854 334.751L730.745 337.573V343.205L725.854 346.021L720.963 343.205Z"/>
<path id="land_1209" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 354.748V349.111L732.594 346.295L737.479 349.111V354.743L732.594 357.564L727.703 354.748Z"/>
<path id="land_1210" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 366.293V360.655L739.327 357.839L744.218 360.655V366.293L739.327 369.109L734.442 366.293Z"/>
<path id="land_1211" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 377.831V372.199L746.067 369.383L750.957 372.205V377.831L746.067 380.653L741.181 377.831Z"/>
<path id="land_1212" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 400.919V395.287L759.546 392.471L764.431 395.287V400.919L759.546 403.741L754.66 400.919Z"/>
<path id="land_1213" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M545.782 19.9979V14.3602L550.668 11.5442L555.553 14.3602V19.9979L550.668 22.8139L545.782 19.9979Z"/>
<path id="land_1214" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 31.5362V25.9098L557.407 23.0825L562.292 25.9098V31.5362L557.407 34.3523L552.516 31.5362Z"/>
<path id="land_1215" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 43.0804V37.4483L564.141 34.6267L569.032 37.4483V43.0804L564.141 45.9021L559.256 43.0804Z"/>
<path id="land_1216" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 54.6246V48.9925L570.88 46.1709L575.766 48.9925V54.6246L570.88 57.4407L565.995 54.6246Z"/>
<path id="land_1217" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M575.172 67.5796L572.729 66.1688V60.5367L577.62 57.7151L582.505 60.5367V66.1632L577.62 68.9848L575.172 67.5796Z"/>
<path id="land_1218" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 77.7127V72.0807L584.359 69.2534L589.244 72.0807V77.7071L584.359 80.5288L579.468 77.7127Z"/>
<path id="land_1219" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 89.2513V83.6192L591.093 80.7976L595.984 83.6192V89.2513L591.093 92.073L586.208 89.2513Z"/>
<path id="land_1220" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 100.795V95.1634L597.833 92.3418L602.718 95.1634V100.795L597.833 103.617L592.947 100.795Z"/>
<path id="land_1221" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 112.339V106.707L604.572 103.886L609.457 106.707V112.339L604.572 115.161L599.681 112.339Z"/>
<path id="land_1222" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 123.883V118.251L611.311 115.43L616.197 118.251V123.883L611.311 126.7L606.421 123.883Z"/>
<path id="land_1223" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 135.422V129.796L618.045 126.974L622.936 129.796V135.422L618.045 138.244L613.16 135.422Z"/>
<path id="land_1224" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 146.966V141.334L624.785 138.518L629.67 141.334V146.966L624.785 149.788L619.9 146.966Z"/>
<path id="land_1225" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 158.51V152.884L631.524 150.062L636.409 152.884V158.51L633.961 159.927L631.524 161.332L626.633 158.51Z"/>
<path id="land_1226" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 170.054V164.422L638.264 161.601L643.143 164.422V170.054L638.258 172.876L633.373 170.054Z"/>
<path id="land_1227" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 181.599V175.966L644.998 173.145L649.883 175.966V181.599L644.998 184.42L640.112 181.599Z"/>
<path id="land_1228" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 193.142V187.51L651.737 184.689L656.622 187.51V193.142L651.737 195.958L646.852 193.142Z"/>
<path id="land_1229" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 204.686V199.054L658.476 196.233L663.361 199.049V204.686L658.476 207.503L653.586 204.686Z"/>
<path id="land_1230" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 216.225V210.599L665.216 207.777L670.101 210.599V216.225L665.216 219.047L660.325 216.225Z"/>
<path id="land_1231" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 227.769V222.143L671.95 219.315L676.84 222.143V227.769L671.95 230.591L667.064 227.769Z"/>
<path id="land_1232" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 239.313V233.681L678.689 230.865L683.574 233.681V239.319L678.689 242.141L673.804 239.313Z"/>
<path id="land_1233" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 250.857V245.225L682.986 243.809L685.428 242.404L690.314 245.225V250.857L685.428 253.673L680.538 250.857Z"/>
<path id="land_1234" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 262.396V256.769L692.168 253.948L697.053 256.769V262.396L692.168 265.218L687.272 262.396Z"/>
<path id="land_1235" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 273.945V268.313L698.902 265.492L703.793 268.313V273.945L698.902 276.761L694.017 273.945Z"/>
<path id="land_1236" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 285.49V279.852L705.641 277.036L710.526 279.852V285.49L705.641 288.306L700.756 285.49Z"/>
<path id="land_1237" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 297.034V291.402L712.381 288.58L717.266 291.396V297.034L712.381 299.85L707.49 297.034Z"/>
<path id="land_1238" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 308.572V302.94L719.114 300.124L724.005 302.946V308.572L719.114 311.394L714.229 308.572Z"/>
<path id="land_1239" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 320.122V314.484L725.854 311.663L730.745 314.49V320.116L725.854 322.938L720.963 320.122Z"/>
<path id="land_1240" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 331.66V326.028L732.594 323.207L737.479 326.028V331.66L732.594 334.482L727.703 331.66Z"/>
<path id="land_1241" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 343.205V337.567L739.327 334.751L744.218 337.567V343.205L739.327 346.021L734.442 343.205Z"/>
<path id="land_1242" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 354.743V349.116L746.067 346.295L750.957 349.116V354.743L746.067 357.564L741.181 354.743Z"/>
<path id="land_1243" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 366.287V360.66L752.806 357.839L757.691 360.66V366.287L752.806 369.103L747.921 366.287Z"/>
<path id="land_1244" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 377.837V372.199L759.546 369.383L764.431 372.199V377.837L759.546 380.653L754.66 377.837Z"/>
<path id="land_1245" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 389.375V383.749L766.285 380.921L771.17 383.743V389.375L766.285 392.197L761.394 389.375Z"/>
<path id="land_1246" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M552.516 8.44812V2.82164L557.407 0L562.292 2.82164V8.44812L557.407 11.2642L552.516 8.44812Z"/>
<path id="land_1247" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M559.256 19.9979V14.3602L564.141 11.5442L569.032 14.3602V19.9923L564.141 22.8139L559.256 19.9979Z"/>
<path id="land_1248" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M565.995 31.5418V25.9042L570.88 23.0825L575.766 25.9098V31.5362L570.88 34.3523L565.995 31.5418Z"/>
<path id="land_1249" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M572.729 43.0804V37.4483L577.62 34.6267L582.505 37.4483V43.0804L577.62 45.8965L572.729 43.0804Z"/>
<path id="land_1250" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 54.6246V48.9925L584.359 46.1709L589.244 48.9925V54.6246L584.359 57.4407L579.468 54.6246Z"/>
<path id="land_1251" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 66.1632V60.5367L591.093 57.7151L595.984 60.5367V66.1632L591.093 68.9848L586.208 66.1632Z"/>
<path id="land_1252" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 77.7127V72.0807L597.833 69.2534L602.718 72.0807V77.7071L597.833 80.5344L592.947 77.7127Z"/>
<path id="land_1253" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 89.2513V83.6192L604.572 80.7976L609.457 83.6192V89.2513L604.572 92.073L599.681 89.2513Z"/>
<path id="land_1254" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 100.795V95.1634L611.311 92.3418L616.197 95.1634V100.795L611.311 103.617L606.421 100.795Z"/>
<path id="land_1255" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 112.339V106.707L618.045 103.886L622.936 106.707V112.334L618.045 115.161L613.16 112.339Z"/>
<path id="land_1256" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 123.883V118.246L624.785 115.43L629.67 118.246V123.883L624.785 126.7L619.9 123.883Z"/>
<path id="land_1257" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 135.422V129.796L631.524 126.974L636.409 129.796V135.422L631.524 138.244L626.633 135.422Z"/>
<path id="land_1258" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 146.966V141.334L638.264 138.518L643.143 141.334V146.966L638.258 149.788L633.373 146.966Z"/>
<path id="land_1259" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 158.51V152.884L644.998 150.062L649.883 152.878V158.516L644.998 161.332L640.112 158.51Z"/>
<path id="land_1260" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 170.054V164.422L651.737 161.601L656.622 164.422V170.054L651.737 172.87L646.852 170.054Z"/>
<path id="land_1261" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 181.599V175.966L658.476 173.145L663.361 175.966V181.599L658.476 184.42L653.586 181.599Z"/>
<path id="land_1262" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 193.137V187.51L665.216 184.689L670.101 187.51V193.137L665.216 195.958L660.325 193.137Z"/>
<path id="land_1263" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 204.686V199.054L671.95 196.233L676.84 199.054V204.686L671.95 207.503L667.064 204.686Z"/>
<path id="land_1264" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 216.225V210.593L678.689 207.777L683.574 210.593V216.231L678.689 219.047L673.804 216.225Z"/>
<path id="land_1265" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 227.769V222.143L685.428 219.315L690.314 222.137V227.775L685.428 230.591L680.538 227.769Z"/>
<path id="land_1266" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 239.313V233.687L692.168 230.865L697.053 233.687V239.313L692.168 242.135L687.272 239.313Z"/>
<path id="land_1267" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 250.857V245.225L698.902 242.404L703.793 245.225V250.857L698.902 253.673L694.017 250.857Z"/>
<path id="land_1268" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 262.402V256.764L705.641 253.948L710.526 256.764V262.402L705.641 265.218L700.756 262.402Z"/>
<path id="land_1269" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 273.945V268.313L712.381 265.492L717.266 268.313V273.945L712.381 276.761L707.49 273.945Z"/>
<path id="land_1270" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 285.484V279.857L719.114 277.036L724.005 279.857V285.484L719.114 288.306L714.229 285.484Z"/>
<path id="land_1271" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 297.034V291.402L725.854 288.58L730.745 291.402V297.028L725.854 299.85L720.963 297.034Z"/>
<path id="land_1272" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 308.572V302.94L732.594 300.118L737.479 302.94V308.572L732.594 311.394L727.703 308.572Z"/>
<path id="land_1273" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 320.122V314.484L739.327 311.663L744.218 314.484V320.122L739.327 322.932L734.442 320.122Z"/>
<path id="land_1274" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 331.655V326.028L746.067 323.207L750.957 326.028V331.655L746.067 334.482L741.181 331.655Z"/>
<path id="land_1275" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 343.205V337.567L752.806 334.751L757.691 337.567V343.205L752.806 346.021L747.921 343.205Z"/>
<path id="land_1276" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 354.743V349.111L759.546 346.295L764.431 349.111V354.748L759.546 357.564L754.66 354.743Z"/>
<path id="land_1277" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 366.287V360.66L766.285 357.839L771.17 360.655V366.293L766.285 369.109L761.394 366.287Z"/>
<path id="land_1278" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 377.831V372.199L773.019 369.383L777.909 372.199V377.831L773.019 380.653L768.134 377.831Z"/>
<path id="land_1279" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M579.468 31.5362V25.9042L584.359 23.0825L589.244 25.9042V31.5362L584.359 34.3523L579.468 31.5362Z"/>
<path id="land_1280" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M586.208 43.0804V37.4483L591.093 34.6267L595.984 37.4483V43.0804L591.093 45.9021L586.208 43.0804Z"/>
<path id="land_1281" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M592.947 54.6247V48.9927L597.833 46.1654L602.718 48.9927V54.6247L597.833 57.4408L592.947 54.6247Z"/>
<path id="land_1282" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 66.1688V60.5367L604.572 57.7151L609.457 60.5367V66.1632L604.572 68.9848L599.681 66.1688Z"/>
<path id="land_1283" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 77.7071V72.0807L611.311 69.2534L616.197 72.0807V77.7071L611.311 80.5288L606.421 77.7071Z"/>
<path id="land_1284" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 89.2513V83.6192L618.045 80.7976L622.936 83.6192V89.2513L618.045 92.073L613.16 89.2513Z"/>
<path id="land_1285" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 100.801V95.1578L624.785 92.3418L629.67 95.1578V100.801L624.785 103.617L619.9 100.801Z"/>
<path id="land_1286" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 112.334V106.707L631.524 103.886L636.409 106.707V112.334L633.961 113.75L631.524 115.155L626.633 112.334Z"/>
<path id="land_1287" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 123.883V118.246L638.264 115.43L643.143 118.246V123.883L638.258 126.7L633.373 123.883Z"/>
<path id="land_1288" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 135.422V129.796L644.998 126.974L649.883 129.79V135.428L644.998 138.244L640.112 135.422Z"/>
<path id="land_1289" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 146.966V141.334L651.737 138.518L656.622 141.334V146.966L651.737 149.788L646.852 146.966Z"/>
<path id="land_1290" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 158.51V152.878L658.476 150.062L663.361 152.878V158.516L658.476 161.332L653.586 158.51Z"/>
<path id="land_1291" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 170.049V164.422L665.216 161.601L670.101 164.422V170.054L665.216 172.87L660.325 170.049Z"/>
<path id="land_1292" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 181.599V175.966L671.95 173.145L676.84 175.966V181.599L671.95 184.42L667.064 181.599Z"/>
<path id="land_1293" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 193.142V187.51L678.689 184.689L681.126 186.099L683.574 187.505V193.142L678.689 195.958L673.804 193.142Z"/>
<path id="land_1294" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 204.686V199.054L685.428 196.233L690.314 199.049V204.686L685.428 207.503L680.538 204.686Z"/>
<path id="land_1295" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 216.225V210.599L692.168 207.777L697.053 210.599V216.225L692.168 219.047L687.272 216.225Z"/>
<path id="land_1296" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 227.769V222.143L698.902 219.321L703.793 222.143V227.769L698.902 230.591L694.017 227.769Z"/>
<path id="land_1297" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 239.319V233.681L705.641 230.865L710.526 233.681V239.319L705.641 242.141L700.756 239.319Z"/>
<path id="land_1298" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 250.857V245.225L712.381 242.404L717.266 245.225V250.857L712.381 253.673L707.49 250.857Z"/>
<path id="land_1299" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 262.396V256.769L719.114 253.948L724.005 256.769V262.396L719.114 265.218L714.229 262.396Z"/>
<path id="land_1300" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 273.945V268.313L725.854 265.492L730.745 268.313V273.94L725.854 276.761L720.963 273.945Z"/>
<path id="land_1301" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 285.49V279.852L732.594 277.036L737.479 279.852V285.49L732.594 288.306L727.703 285.49Z"/>
<path id="land_1302" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 297.034V291.396L739.327 288.58L744.218 291.396V297.034L739.327 299.85L734.442 297.034Z"/>
<path id="land_1303" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 308.572V302.94L746.067 300.118L750.957 302.946V308.572L746.067 311.394L741.181 308.572Z"/>
<path id="land_1304" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 320.122V314.484L752.806 311.668L757.691 314.484V320.116L752.806 322.932L747.921 320.122Z"/>
<path id="land_1305" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 331.66V326.028L759.546 323.207L764.431 326.028V331.66L759.546 334.482L754.66 331.66Z"/>
<path id="land_1306" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 343.205V337.567L766.285 334.751L771.17 337.567V343.205L766.285 346.021L761.394 343.205Z"/>
<path id="land_1307" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 354.743V349.116L773.019 346.295L777.909 349.116V354.743L775.461 356.154L773.019 357.564L768.134 354.743Z"/>
<path id="land_1308" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 366.287V360.66L779.758 357.839L784.649 360.66V366.287L779.758 369.109L774.873 366.287Z"/>
<path id="land_1309" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 377.831V372.199L786.498 369.383L791.383 372.199V377.837L786.498 380.653L781.607 377.831Z"/>
<path id="land_1310" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M599.681 43.0804V37.4483L604.572 34.6267L609.457 37.4483V43.0804L604.572 45.8965L599.681 43.0804Z"/>
<path id="land_1311" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M606.421 54.6247V48.9927L611.311 46.1654L616.197 48.9927V54.6192L611.311 57.4408L606.421 54.6247Z"/>
<path id="land_1312" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 66.1632V60.5367L618.045 57.7151L622.936 60.5367V66.1632L618.045 68.9848L613.16 66.1632Z"/>
<path id="land_1313" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M622.348 79.118L619.9 77.7127V72.0751L624.785 69.2534L629.67 72.0751V77.7127L627.227 79.118L624.785 80.5288L622.348 79.118Z"/>
<path id="land_1314" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 89.2512V83.6191L631.524 80.803L636.409 83.6191V89.2512L631.524 92.0728L626.633 89.2512Z"/>
<path id="land_1315" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 100.795V95.1634L638.264 92.3418L643.143 95.1634V100.795L638.258 103.617L633.373 100.795Z"/>
<path id="land_1316" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 112.334V106.707L644.998 103.886L649.883 106.702V112.339L644.998 115.161L640.112 112.334Z"/>
<path id="land_1317" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 123.883V118.246L651.737 115.43L656.622 118.246V123.883L651.737 126.7L646.852 123.883Z"/>
<path id="land_1318" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 135.428V129.796L658.476 126.974L663.361 129.79V135.428L658.476 138.244L653.586 135.428Z"/>
<path id="land_1319" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 146.966V141.34L665.216 138.518L670.101 141.334V146.966L665.216 149.788L660.325 146.966Z"/>
<path id="land_1320" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 158.51V152.884L671.95 150.062L676.84 152.884V158.51L674.392 159.927L671.95 161.332L667.064 158.51Z"/>
<path id="land_1321" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 170.054V164.422L678.689 161.601L683.574 164.422V170.054L678.689 172.876L673.804 170.054Z"/>
<path id="land_1322" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 181.599V175.966L685.428 173.145L690.314 175.966V181.599L685.428 184.42L680.538 181.599Z"/>
<path id="land_1323" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 193.137V187.51L692.168 184.689L697.053 187.51V193.137L692.168 195.958L687.272 193.137Z"/>
<path id="land_1324" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 204.686V199.054L698.902 196.233L703.793 199.054V204.681L698.902 207.503L694.017 204.686Z"/>
<path id="land_1325" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 216.231V210.593L705.641 207.777L710.526 210.593V216.231L705.641 219.047L700.756 216.231Z"/>
<path id="land_1326" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 227.769V222.143L712.381 219.321L717.266 222.137V227.775L712.381 230.591L707.49 227.769Z"/>
<path id="land_1327" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 239.313V233.681L719.114 230.865L724.005 233.687V239.313L719.114 242.135L714.229 239.313Z"/>
<path id="land_1328" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 250.857V245.225L725.854 242.404L730.745 245.231V250.857L725.854 253.673L720.963 250.857Z"/>
<path id="land_1329" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 262.402V256.769L732.594 253.948L737.479 256.769V262.402L732.594 265.218L727.703 262.402Z"/>
<path id="land_1330" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 273.945V268.313L739.327 265.492L744.218 268.313V273.945L739.327 276.761L734.442 273.945Z"/>
<path id="land_1331" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 285.49V279.852L746.067 277.036L750.957 279.857V285.484L746.067 288.306L741.181 285.49Z"/>
<path id="land_1332" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 297.034V291.402L750.364 289.985L752.806 288.58L755.249 289.985L757.691 291.402V297.028L752.806 299.85L747.921 297.034Z"/>
<path id="land_1333" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 308.572V302.94L759.546 300.124L764.431 302.94V308.572L759.546 311.394L754.66 308.572Z"/>
<path id="land_1334" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 320.122V314.484L766.285 311.663L771.17 314.484V320.122L766.285 322.932L761.394 320.122Z"/>
<path id="land_1335" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 331.655V326.028L773.019 323.207L777.909 326.028V331.655L773.019 334.482L768.134 331.655Z"/>
<path id="land_1336" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 343.205V337.567L779.758 334.751L784.649 337.567V343.205L779.758 346.021L774.873 343.205Z"/>
<path id="land_1337" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 354.743V349.116L786.498 346.295L791.383 349.111V354.748L786.498 357.564L781.607 354.743Z"/>
<path id="land_1338" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 366.287V360.66L793.237 357.839L798.122 360.655V366.293L793.237 369.109L788.346 366.287Z"/>
<path id="land_1339" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M613.16 43.0804V37.4483L618.045 34.6267L622.936 37.4483V43.0804L618.045 45.9021L613.16 43.0804Z"/>
<path id="land_1340" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 54.6246V48.9925L624.785 46.1709L629.67 48.9925V54.6246L624.785 57.4407L619.9 54.6246Z"/>
<path id="land_1341" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 66.1632V60.5367L631.524 57.7151L636.409 60.5367V66.1632L631.524 68.9848L626.633 66.1632Z"/>
<path id="land_1342" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 77.7071V72.0807L638.264 69.2534L643.143 72.0807V77.7127L638.258 80.5288L633.373 77.7071Z"/>
<path id="land_1343" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 89.2513V83.6192L644.998 80.7976L649.883 83.6192V89.2513L644.998 92.073L640.112 89.2513Z"/>
<path id="land_1344" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 100.795V95.1634L651.737 92.3418L656.622 95.1634V100.795L651.737 103.617L646.852 100.795Z"/>
<path id="land_1345" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 112.339V106.707L658.476 103.886L663.361 106.702V112.339L658.476 115.161L653.586 112.339Z"/>
<path id="land_1346" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 123.883V118.251L665.216 115.43L670.101 118.251V123.883L665.216 126.7L660.325 123.883Z"/>
<path id="land_1347" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 135.422V129.796L671.95 126.974L676.84 129.796V135.422L671.95 138.244L667.064 135.422Z"/>
<path id="land_1348" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 146.966V141.334L678.689 138.518L683.574 141.334V146.966L678.689 149.788L673.804 146.966Z"/>
<path id="land_1349" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 158.51V152.884L685.428 150.062L690.314 152.878V158.516L685.428 161.332L680.538 158.51Z"/>
<path id="land_1350" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 170.049V164.428L692.168 161.601L697.053 164.428V170.049L692.168 172.87L687.272 170.049Z"/>
<path id="land_1351" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 181.599V175.966L698.902 173.145L703.793 175.966V181.599L698.902 184.42L694.017 181.599Z"/>
<path id="land_1352" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 193.142V187.505L703.199 186.099L705.641 184.689L708.078 186.099L710.526 187.505V193.142L705.641 195.958L700.756 193.142Z"/>
<path id="land_1353" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 204.686V199.054L712.381 196.233L717.266 199.049V204.686L712.381 207.503L707.49 204.686Z"/>
<path id="land_1354" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 216.225V210.599L719.114 207.777L724.005 210.599V216.225L719.114 219.047L714.229 216.225Z"/>
<path id="land_1355" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 227.769V222.143L725.854 219.315L730.745 222.143V227.769L725.854 230.591L720.963 227.769Z"/>
<path id="land_1356" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 239.313V233.681L732.594 230.865L737.479 233.681V239.313L732.594 242.141L727.703 239.313Z"/>
<path id="land_1357" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 250.857V245.225L739.327 242.404L744.218 245.225V250.857L739.327 253.673L734.442 250.857Z"/>
<path id="land_1358" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 262.402V256.769L746.067 253.948L750.957 256.769V262.396L746.067 265.218L741.181 262.402Z"/>
<path id="land_1359" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 273.945V268.313L752.806 265.492L757.691 268.313V273.945L752.806 276.761L747.921 273.945Z"/>
<path id="land_1360" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 285.49V279.852L759.546 277.036L764.431 279.852V285.49L759.546 288.306L754.66 285.49Z"/>
<path id="land_1361" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 297.034V291.402L763.842 289.985L766.285 288.58L771.17 291.396V297.034L766.285 299.85L761.394 297.034Z"/>
<path id="land_1362" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 308.572V302.94L773.019 300.124L777.909 302.94V308.572L773.019 311.394L768.134 308.572Z"/>
<path id="land_1363" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 320.122V314.484L779.758 311.663L784.649 314.484V320.122L779.758 322.932L774.873 320.122Z"/>
<path id="land_1364" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 331.655V326.028L786.498 323.207L791.383 326.028V331.66L786.498 334.482L781.607 331.655Z"/>
<path id="land_1365" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 343.205V337.567L793.237 334.751L798.122 337.567V343.21L793.237 346.021L788.346 343.205Z"/>
<path id="land_1366" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 354.743V349.116L799.971 346.295L804.862 349.111V354.748L799.971 357.564L795.086 354.743Z"/>
<path id="land_1367" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 366.287V360.66L806.71 357.839L811.601 360.66V366.287L806.71 369.109L801.82 366.287Z"/>
<path id="land_1368" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M619.9 31.5418V25.9042L624.785 23.0825L629.67 25.9042V31.5418L624.785 34.3523L619.9 31.5418Z"/>
<path id="land_1369" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M626.633 43.0804V37.4483L631.524 34.6267L636.409 37.4483V43.0804L631.524 45.8965L626.633 43.0804Z"/>
<path id="land_1370" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 54.6246V48.9925L638.264 46.1709L643.143 48.9925V54.6246L638.258 57.4407L633.373 54.6246Z"/>
<path id="land_1371" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 66.1632V60.5367L644.998 57.7151L649.883 60.5311V66.1688L644.998 68.9848L640.112 66.1632Z"/>
<path id="land_1372" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 77.7127V72.0807L651.737 69.2534L656.622 72.0807V77.7127L651.737 80.5288L646.852 77.7127Z"/>
<path id="land_1373" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 89.2513V83.6192L658.476 80.7976L663.361 83.6192V89.2513L658.476 92.073L653.586 89.2513Z"/>
<path id="land_1374" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 100.795V95.1634L665.216 92.3418L670.101 95.1634V100.795L665.216 103.617L660.325 100.795Z"/>
<path id="land_1375" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 112.334V106.707L671.95 103.886L676.84 106.707V112.339L671.95 115.161L667.064 112.334Z"/>
<path id="land_1376" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 123.883V118.246L678.689 115.43L683.574 118.246V123.883L678.689 126.7L673.804 123.883Z"/>
<path id="land_1377" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 135.422V129.796L685.428 126.974L690.314 129.79V135.428L685.428 138.244L680.538 135.422Z"/>
<path id="land_1378" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 146.961V141.34L692.168 138.518L697.053 141.34V146.961L692.168 149.788L687.272 146.961Z"/>
<path id="land_1379" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 158.51V152.884L698.902 150.062L703.793 152.884V158.51L698.902 161.332L694.017 158.51Z"/>
<path id="land_1380" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 170.054V164.422L705.641 161.601L710.526 164.422V170.054L705.641 172.876L700.756 170.054Z"/>
<path id="land_1381" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 181.599V175.966L712.381 173.145L717.266 175.966V181.599L712.381 184.42L707.49 181.599Z"/>
<path id="land_1382" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 193.137V187.51L719.114 184.689L724.005 187.51V193.137L719.114 195.958L714.229 193.137Z"/>
<path id="land_1383" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 204.686V199.054L725.854 196.233L730.745 199.054V204.681L725.854 207.503L720.963 204.686Z"/>
<path id="land_1384" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 216.225V210.593L732.594 207.777L737.479 210.593V216.225L732.594 219.047L727.703 216.225Z"/>
<path id="land_1385" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 227.775V222.137L739.327 219.321L744.218 222.137V227.775L739.327 230.591L734.442 227.775Z"/>
<path id="land_1386" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 239.313V233.681L746.067 230.865L750.957 233.681V239.313L746.067 242.141L741.181 239.313Z"/>
<path id="land_1387" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 250.857V245.225L750.364 243.809L752.806 242.409L755.249 243.814L757.691 245.225V250.857L752.806 253.673L747.921 250.857Z"/>
<path id="land_1388" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 262.402V256.769L759.546 253.948L764.431 256.764V262.402L759.546 265.218L754.66 262.402Z"/>
<path id="land_1389" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 273.945V268.313L766.285 265.492L771.17 268.313V273.945L766.285 276.761L761.394 273.945Z"/>
<path id="land_1390" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 285.484V279.857L773.019 277.036L777.909 279.852V285.49L773.019 288.306L768.134 285.484Z"/>
<path id="land_1391" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 297.034V291.402L779.758 288.58L784.649 291.402V297.034L779.758 299.85L774.873 297.034Z"/>
<path id="land_1392" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 308.572V302.94L786.498 300.124L791.383 302.94V308.572L786.498 311.394L781.607 308.572Z"/>
<path id="land_1393" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 320.122V314.484L793.237 311.663L798.122 314.484V320.122L793.237 322.938L788.346 320.122Z"/>
<path id="land_1394" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 331.66V326.028L799.971 323.207L804.862 326.028V331.66L799.971 334.482L795.086 331.66Z"/>
<path id="land_1395" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 343.205V337.567L806.71 334.751L811.601 337.573V343.205L806.71 346.021L801.82 343.205Z"/>
<path id="land_1396" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 354.743V349.111L813.45 346.295L818.335 349.111V354.748L813.45 357.564L808.565 354.743Z"/>
<path id="land_1397" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M633.373 31.5362V25.9042L638.264 23.0825L643.143 25.9042V31.5362L638.258 34.3523L633.373 31.5362Z"/>
<path id="land_1398" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M640.112 43.0804V37.4483L644.998 34.6267L649.883 37.4483V43.0804L644.998 45.9021L640.112 43.0804Z"/>
<path id="land_1399" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 54.6246V48.9925L651.737 46.1709L656.622 48.9925V54.6246L651.737 57.4407L646.852 54.6246Z"/>
<path id="land_1400" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 66.1632V60.5367L658.476 57.7151L663.361 60.5311V66.1688L658.476 68.9848L653.586 66.1632Z"/>
<path id="land_1401" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 77.7071V72.0807L665.216 69.2534L670.101 72.0807V77.7071L665.216 80.5288L660.325 77.7071Z"/>
<path id="land_1402" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 89.2513V83.6192L671.95 80.7976L676.84 83.6192V89.2513L671.95 92.073L667.064 89.2513Z"/>
<path id="land_1403" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 100.795V95.1634L678.689 92.3418L683.574 95.1578V100.801L678.689 103.617L673.804 100.795Z"/>
<path id="land_1404" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 112.339V106.707L685.428 103.886L690.314 106.702V112.339L685.428 115.161L680.538 112.339Z"/>
<path id="land_1405" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 123.878V118.251L692.168 115.43L697.053 118.251V123.878L692.168 126.7L687.272 123.878Z"/>
<path id="land_1406" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 135.422V129.796L698.902 126.974L703.793 129.796V135.422L698.902 138.244L694.017 135.422Z"/>
<path id="land_1407" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 146.966V141.334L705.641 138.518L710.526 141.334V146.966L705.641 149.788L700.756 146.966Z"/>
<path id="land_1408" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 158.51V152.884L712.381 150.062L717.266 152.878V158.516L712.381 161.332L707.49 158.51Z"/>
<path id="land_1409" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 170.054V164.422L719.114 161.601L724.005 164.422V170.049L719.114 172.87L714.229 170.054Z"/>
<path id="land_1410" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 181.599V175.966L725.854 173.145L730.745 175.966V181.593L725.854 184.42L720.963 181.599Z"/>
<path id="land_1411" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 193.142V187.51L732.594 184.689L737.479 187.51V193.142L732.594 195.958L727.703 193.142Z"/>
<path id="land_1412" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 204.686V199.049L739.327 196.233L744.218 199.049V204.686L739.327 207.503L734.442 204.686Z"/>
<path id="land_1413" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 216.225V210.599L746.067 207.777L750.957 210.599V216.225L746.067 219.047L741.181 216.225Z"/>
<path id="land_1414" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 227.769V222.143L750.364 220.726L752.806 219.321L757.691 222.143V227.769L752.806 230.591L747.921 227.769Z"/>
<path id="land_1415" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 239.313V233.681L759.546 230.865L764.431 233.681V239.319L759.546 242.135L754.66 239.313Z"/>
<path id="land_1416" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 250.857V245.225L763.842 243.809L766.285 242.404L771.17 245.225V250.857L766.285 253.673L761.394 250.857Z"/>
<path id="land_1417" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 262.396V256.769L773.019 253.948L775.456 255.359L777.909 256.769V262.402L773.019 265.218L768.134 262.396Z"/>
<path id="land_1418" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 273.945V268.313L779.758 265.492L784.649 268.313V273.945L779.758 276.761L774.873 273.945Z"/>
<path id="land_1419" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 285.484V279.857L786.498 277.036L791.383 279.852V285.49L786.498 288.306L781.607 285.484Z"/>
<path id="land_1420" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 297.034V291.402L793.237 288.58L798.122 291.396V297.034L793.237 299.85L788.346 297.034Z"/>
<path id="land_1421" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 308.572V302.94L799.971 300.118L804.862 302.94V308.572L799.971 311.394L795.086 308.572Z"/>
<path id="land_1422" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 320.116V314.484L806.71 311.663L811.601 314.49V320.116L806.71 322.932L801.82 320.116Z"/>
<path id="land_1423" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 331.66V326.028L813.45 323.207L818.335 326.023V331.66L813.45 334.482L808.565 331.66Z"/>
<path id="land_1424" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 343.205V337.573L820.189 334.751L825.074 337.567V343.205L820.189 346.021L815.299 343.205Z"/>
<path id="land_1425" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M646.852 31.5362V25.9042L651.737 23.0825L656.622 25.9042V31.5362L651.737 34.3523L646.852 31.5362Z"/>
<path id="land_1426" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M653.586 43.0804V37.4483L658.476 34.6267L663.361 37.4483V43.0804L658.476 45.8965L653.586 43.0804Z"/>
<path id="land_1427" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M660.325 54.619V48.9925L665.216 46.1709L670.101 48.9925V54.6246L665.216 57.4407L660.325 54.619Z"/>
<path id="land_1428" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 66.1632V60.5367L671.95 57.7151L676.84 60.5367V66.1632L671.95 68.9848L667.064 66.1632Z"/>
<path id="land_1429" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 77.7127V72.0807L678.689 69.2534L683.574 72.0751V77.7127L681.132 79.118L678.689 80.5288L673.804 77.7127Z"/>
<path id="land_1430" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 89.2513V83.6192L685.428 80.7976L690.314 83.6192V89.2513L685.428 92.073L680.538 89.2513Z"/>
<path id="land_1431" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 100.795V95.1634L692.168 92.3418L697.053 95.1634V100.795L692.168 103.617L687.272 100.795Z"/>
<path id="land_1432" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 112.339V106.707L698.902 103.886L703.793 106.707V112.334L698.902 115.161L694.017 112.339Z"/>
<path id="land_1433" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 123.883V118.246L705.641 115.43L710.526 118.246V123.883L705.641 126.7L700.756 123.883Z"/>
<path id="land_1434" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 135.422V129.796L712.381 126.974L717.266 129.79V135.428L712.381 138.244L707.49 135.422Z"/>
<path id="land_1435" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 146.966V141.334L719.114 138.518L724.005 141.34V146.966L719.114 149.788L714.229 146.966Z"/>
<path id="land_1436" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 158.51V152.884L725.854 150.062L730.745 152.884V158.51L725.854 161.332L720.963 158.51Z"/>
<path id="land_1437" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 170.054V164.422L732.594 161.601L737.479 164.422V170.054L732.594 172.87L727.703 170.054Z"/>
<path id="land_1438" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 181.599V175.966L739.327 173.145L744.218 175.966V181.599L739.327 184.42L734.442 181.599Z"/>
<path id="land_1439" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 193.137V187.51L746.067 184.689L750.957 187.51V193.137L746.067 195.958L741.181 193.137Z"/>
<path id="land_1440" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 204.686V199.054L752.806 196.233L757.691 199.054V204.686L752.806 207.503L747.921 204.686Z"/>
<path id="land_1441" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 216.225V210.593L759.546 207.777L764.431 210.593V216.231L759.546 219.047L754.66 216.225Z"/>
<path id="land_1442" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 227.769V222.143L763.842 220.726L766.285 219.321L771.17 222.137V227.775L766.285 230.591L761.394 227.769Z"/>
<path id="land_1443" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 239.313V233.681L773.019 230.865L777.909 233.681V239.313L773.019 242.135L768.134 239.313Z"/>
<path id="land_1444" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 250.857V245.225L779.758 242.404L784.649 245.225V250.857L779.758 253.673L774.873 250.857Z"/>
<path id="land_1445" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 273.945V268.313L793.237 265.492L798.122 268.308V273.945L793.237 276.761L788.346 273.945Z"/>
<path id="land_1446" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 285.49V279.852L799.971 277.036L804.862 279.852V285.49L799.971 288.306L795.086 285.49Z"/>
<path id="land_1447" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 297.028V291.402L806.71 288.58L809.153 289.985L811.601 291.402V297.028L806.71 299.85L801.82 297.028Z"/>
<path id="land_1448" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 308.572V302.94L813.45 300.118L818.335 302.94V308.572L813.45 311.394L808.565 308.572Z"/>
<path id="land_1449" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 320.116V314.484L820.189 311.663L825.074 314.484V320.122L820.189 322.938L815.299 320.116Z"/>
<path id="land_1450" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 331.655V326.028L826.923 323.207L831.814 326.028V331.655L826.923 334.482L822.038 331.655Z"/>
<path id="land_1451" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M667.064 43.0804V37.4483L671.95 34.6267L676.84 37.4483V43.0804L671.95 45.9021L667.064 43.0804Z"/>
<path id="land_1452" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M673.804 54.6246V48.9925L678.689 46.1709L683.574 48.9925V54.6246L678.689 57.4407L673.804 54.6246Z"/>
<path id="land_1453" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 66.1632V60.5367L685.428 57.7151L690.314 60.5311V66.1688L685.428 68.9848L680.538 66.1632Z"/>
<path id="land_1454" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 77.7071V72.0807L692.168 69.2534L697.053 72.0807V77.7071L692.168 80.5288L687.272 77.7071Z"/>
<path id="land_1455" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 89.2513V83.6192L698.902 80.7976L703.793 83.6192V89.2513L698.902 92.073L694.017 89.2513Z"/>
<path id="land_1456" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 100.801V95.1578L705.641 92.3418L710.526 95.1578V100.801L705.641 103.617L700.756 100.801Z"/>
<path id="land_1457" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 112.339V106.707L712.381 103.886L717.266 106.702V112.339L712.381 115.161L707.49 112.339Z"/>
<path id="land_1458" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 123.883V118.251L719.114 115.43L724.005 118.251V123.883L719.114 126.7L714.229 123.883Z"/>
<path id="land_1459" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 135.422V129.796L725.854 126.974L730.745 129.796V135.422L725.854 138.244L720.963 135.422Z"/>
<path id="land_1460" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 146.966V141.334L732.594 138.518L737.479 141.334V146.966L732.594 149.788L727.703 146.966Z"/>
<path id="land_1461" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 158.516V152.878L739.327 150.062L744.218 152.878V158.516L739.327 161.332L734.442 158.516Z"/>
<path id="land_1462" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 170.054V164.422L746.067 161.601L750.957 164.422V170.054L746.067 172.87L741.181 170.054Z"/>
<path id="land_1463" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 181.598V175.966L752.806 173.15L757.691 175.966V181.598L752.806 184.42L747.921 181.598Z"/>
<path id="land_1464" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 193.142V187.51L759.546 184.689L764.431 187.505V193.142L759.546 195.958L754.66 193.142Z"/>
<path id="land_1465" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 204.686V199.054L766.285 196.233L771.17 199.054V204.686L766.285 207.503L761.394 204.686Z"/>
<path id="land_1466" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 216.225V210.599L773.019 207.777L777.909 210.599V216.225L773.019 219.047L768.134 216.225Z"/>
<path id="land_1467" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 227.769V222.137L779.758 219.321L784.649 222.143V227.769L779.758 230.591L774.873 227.769Z"/>
<path id="land_1468" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 239.313V233.681L786.498 230.865L791.383 233.681V239.313L786.498 242.135L781.607 239.313Z"/>
<path id="land_1469" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 273.945V268.313L806.71 265.492L811.601 268.313V273.945L806.71 276.761L801.82 273.945Z"/>
<path id="land_1470" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M680.538 43.0804V37.4483L685.428 34.6267L690.314 37.4427V43.086L685.428 45.9021L680.538 43.0804Z"/>
<path id="land_1471" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M687.272 54.619V48.9981L692.168 46.1709L697.053 48.9981V54.619L692.168 57.4407L687.272 54.619Z"/>
<path id="land_1472" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 66.1632V60.5367L698.902 57.7151L703.793 60.5367V66.1632L698.902 68.9848L694.017 66.1632Z"/>
<path id="land_1473" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 77.7127V72.0751L705.641 69.2534L710.526 72.0751V77.7127L708.078 79.118L705.641 80.5288L700.756 77.7127Z"/>
<path id="land_1474" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 89.2513V83.6192L712.381 80.7976L717.266 83.6192V89.2513L712.381 92.073L707.49 89.2513Z"/>
<path id="land_1475" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 100.795V95.1634L719.114 92.3418L724.005 95.1634V100.795L719.114 103.617L714.229 100.795Z"/>
<path id="land_1476" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 112.339V106.707L725.854 103.886L730.745 106.707V112.334L725.854 115.161L720.963 112.339Z"/>
<path id="land_1477" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 123.883V118.246L732.594 115.43L737.479 118.246V123.883L732.594 126.7L727.703 123.883Z"/>
<path id="land_1478" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 135.428V129.79L739.327 126.974L744.218 129.79V135.428L739.327 138.244L734.442 135.428Z"/>
<path id="land_1479" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 146.966V141.334L746.067 138.518L750.957 141.34V146.966L746.067 149.788L741.181 146.966Z"/>
<path id="land_1480" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M750.364 159.927L747.921 158.51V152.884L752.806 150.062L757.691 152.884V158.51L755.249 159.927L752.806 161.332L750.364 159.927Z"/>
<path id="land_1481" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 170.054V164.422L759.546 161.601L764.431 164.422V170.054L759.546 172.87L754.66 170.054Z"/>
<path id="land_1482" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 181.599V175.966L766.285 173.145L771.17 175.966V181.599L766.285 184.42L761.394 181.599Z"/>
<path id="land_1483" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 193.137V187.51L773.019 184.689L775.456 186.099L777.909 187.51V193.137L773.019 195.958L768.134 193.137Z"/>
<path id="land_1484" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 204.686V199.054L779.758 196.233L784.649 199.054V204.686L779.758 207.503L774.873 204.686Z"/>
<path id="land_1485" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 216.225V210.599L786.498 207.777L791.383 210.593V216.225L786.498 219.047L781.607 216.225Z"/>
<path id="land_1486" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 227.769V222.137L793.237 219.321L798.122 222.137V227.775L793.237 230.591L788.346 227.769Z"/>
<path id="land_1487" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 239.313V233.681L799.971 230.865L804.862 233.681V239.313L799.971 242.141L795.086 239.313Z"/>
<path id="land_1488" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 250.857V245.225L804.268 243.809L806.71 242.409L811.601 245.225V250.857L806.71 253.673L801.82 250.857Z"/>
<path id="land_1489" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M694.017 43.0804V37.4483L698.902 34.6267L703.793 37.4483V43.0804L698.902 45.9021L694.017 43.0804Z"/>
<path id="land_1490" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M700.756 54.6246V48.9925L705.641 46.1709L710.526 48.9925V54.6246L705.641 57.4407L700.756 54.6246Z"/>
<path id="land_1491" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 66.1632V60.5367L712.381 57.7151L717.266 60.5311V66.1688L712.381 68.9848L707.49 66.1632Z"/>
<path id="land_1492" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 77.7071V72.0807L719.114 69.2534L724.005 72.0807V77.7071L719.114 80.5288L714.229 77.7071Z"/>
<path id="land_1493" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 89.2513V83.6192L725.854 80.7976L730.745 83.6248V89.2457L725.854 92.073L720.963 89.2513Z"/>
<path id="land_1494" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 100.795V95.1634L732.594 92.3418L737.479 95.1634V100.795L732.594 103.617L727.703 100.795Z"/>
<path id="land_1495" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 112.339V106.702L739.327 103.886L744.218 106.702V112.339L739.327 115.161L734.442 112.339Z"/>
<path id="land_1496" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 123.883V118.251L746.067 115.43L750.957 118.251V123.883L746.067 126.7L741.181 123.883Z"/>
<path id="land_1497" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 135.422V129.796L752.806 126.974L757.691 129.796V135.422L752.806 138.244L747.921 135.422Z"/>
<path id="land_1498" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 146.966V141.334L759.546 138.518L764.431 141.334V146.966L759.546 149.788L754.66 146.966Z"/>
<path id="land_1499" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M763.837 159.927L761.394 158.51V152.884L766.285 150.062L771.17 152.878V158.51L766.285 161.332L763.837 159.927Z"/>
<path id="land_1500" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 170.054V164.422L773.019 161.601L777.909 164.422V170.054L773.019 172.87L768.134 170.054Z"/>
<path id="land_1501" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 181.599V175.966L779.758 173.145L784.649 175.966V181.599L779.758 184.42L774.873 181.599Z"/>
<path id="land_1502" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 193.137V187.51L786.498 184.689L791.383 187.51V193.142L786.498 195.958L781.607 193.137Z"/>
<path id="land_1503" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 204.686V199.054L793.237 196.233L798.122 199.049V204.686L793.237 207.503L788.346 204.686Z"/>
<path id="land_1504" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 216.225V210.593L799.971 207.777L804.862 210.593V216.225L799.971 219.047L795.086 216.225Z"/>
<path id="land_1505" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 227.769V222.143L806.71 219.321L811.601 222.143V227.769L806.71 230.591L801.82 227.769Z"/>
<path id="land_1506" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 239.313V233.681L813.45 230.865L818.335 233.681V239.319L813.45 242.141L808.565 239.313Z"/>
<path id="land_1507" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 250.857V245.225L820.189 242.404L825.074 245.225V250.857L820.189 253.673L815.299 250.857Z"/>
<path id="land_1508" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 262.396V256.769L826.923 253.948L829.36 255.359L831.814 256.769V262.402L826.923 265.218L822.038 262.396Z"/>
<path id="land_1509" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 273.945V268.308L833.662 265.492L838.553 268.313V273.945L833.662 276.761L828.777 273.945Z"/>
<path id="land_1510" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 285.49V279.852L840.402 277.036L845.287 279.857V285.484L840.402 288.306L835.511 285.49Z"/>
<path id="land_1511" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M707.49 43.0804V37.4483L712.381 34.6267L717.266 37.4483V43.0804L712.381 45.9021L707.49 43.0804Z"/>
<path id="land_1512" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 54.6246V48.9925L719.114 46.1709L724.005 48.9925V54.619L719.114 57.4407L714.229 54.6246Z"/>
<path id="land_1513" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 66.1632V60.5367L725.854 57.7151L730.745 60.5367V66.1632L725.854 68.9848L720.963 66.1632Z"/>
<path id="land_1514" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 77.7127V72.0807L732.594 69.2534L737.479 72.0807V77.7127L732.594 80.5288L727.703 77.7127Z"/>
<path id="land_1515" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 89.2513V83.6192L739.327 80.7976L744.218 83.6192V89.2513L739.327 92.073L734.442 89.2513Z"/>
<path id="land_1516" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 100.795V95.1634L746.067 92.3418L750.957 95.1634V100.795L746.067 103.617L741.181 100.795Z"/>
<path id="land_1517" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 112.339V106.707L752.806 103.886L757.691 106.707V112.334L752.806 115.155L747.921 112.339Z"/>
<path id="land_1518" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 123.883V118.246L759.546 115.43L764.431 118.246V123.883L759.546 126.7L754.66 123.883Z"/>
<path id="land_1519" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 135.422V129.796L766.285 126.974L771.17 129.79V135.428L766.285 138.244L761.394 135.422Z"/>
<path id="land_1520" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 146.966V141.334L773.019 138.518L777.909 141.334V146.966L773.019 149.788L768.134 146.966Z"/>
<path id="land_1521" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 158.51V152.878L779.758 150.062L784.649 152.884V158.51L779.758 161.332L774.873 158.51Z"/>
<path id="land_1522" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 170.054V164.422L786.498 161.601L791.383 164.422V170.054L786.498 172.87L781.607 170.054Z"/>
<path id="land_1523" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 181.599V175.966L793.237 173.145L798.122 175.961V181.599L793.237 184.42L788.346 181.599Z"/>
<path id="land_1524" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 193.137V187.51L799.971 184.689L804.862 187.51V193.142L799.971 195.958L795.086 193.137Z"/>
<path id="land_1525" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 204.686V199.054L806.71 196.233L811.601 199.054V204.686L806.71 207.503L801.82 204.686Z"/>
<path id="land_1526" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 216.225V210.593L813.45 207.777L818.335 210.593V216.231L813.45 219.047L808.565 216.225Z"/>
<path id="land_1527" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 227.769V222.143L820.189 219.321L825.074 222.137V227.775L820.189 230.591L815.299 227.769Z"/>
<path id="land_1528" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 239.313V233.681L826.923 230.865L831.814 233.681V239.313L826.923 242.135L822.038 239.313Z"/>
<path id="land_1529" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 250.857V245.225L831.22 243.809L833.662 242.409L838.553 245.225V250.857L833.662 253.673L828.777 250.857Z"/>
<path id="land_1530" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 262.402V256.769L840.402 253.948L845.287 256.769V262.396L840.402 265.218L835.511 262.402Z"/>
<path id="land_1531" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 273.945V268.313L847.141 265.492L852.027 268.308V273.945L847.141 276.761L842.251 273.945Z"/>
<path id="land_1532" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 285.49V279.852L853.875 277.036L856.312 278.441L858.766 279.852V285.49L856.318 286.895L853.875 288.306L848.99 285.49Z"/>
<path id="land_1533" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M714.229 31.5362V25.9098L719.114 23.0825L724.005 25.9098V31.5362L719.114 34.3523L714.229 31.5362Z"/>
<path id="land_1534" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M720.963 43.0804V37.4483L725.854 34.6267L730.745 37.4483V43.0804L725.854 45.9021L720.963 43.0804Z"/>
<path id="land_1535" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 54.6246V48.9925L732.594 46.1709L737.479 48.9925V54.6246L732.594 57.4407L727.703 54.6246Z"/>
<path id="land_1536" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 66.1688V60.5311L739.327 57.7151L744.218 60.5311V66.1688L739.327 68.9848L734.442 66.1688Z"/>
<path id="land_1537" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 77.7071V72.0807L746.067 69.2534L750.957 72.0807V77.7071L746.067 80.5288L741.181 77.7071Z"/>
<path id="land_1538" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 89.2512V83.6191L752.806 80.803L757.691 83.6191V89.2512L752.806 92.0728L747.921 89.2512Z"/>
<path id="land_1539" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 100.795V95.1634L759.546 92.3418L764.431 95.1578V100.801L759.546 103.617L754.66 100.795Z"/>
<path id="land_1540" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 112.339V106.707L766.285 103.886L771.17 106.707V112.339L766.285 115.161L761.394 112.339Z"/>
<path id="land_1541" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 123.883V118.251L773.019 115.43L777.909 118.251V123.883L773.019 126.7L768.134 123.883Z"/>
<path id="land_1542" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 135.428V129.796L779.758 126.974L784.649 129.796V135.422L779.758 138.244L774.873 135.428Z"/>
<path id="land_1543" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 146.966V141.334L786.498 138.518L791.383 141.334V146.966L786.498 149.788L781.607 146.966Z"/>
<path id="land_1544" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 158.51V152.878L793.237 150.062L798.122 152.878V158.516L793.237 161.332L788.346 158.51Z"/>
<path id="land_1545" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 170.054V164.422L799.971 161.601L804.862 164.422V170.054L799.971 172.87L795.086 170.054Z"/>
<path id="land_1546" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 181.599V175.966L806.71 173.145L811.601 175.966V181.599L806.71 184.42L801.82 181.599Z"/>
<path id="land_1547" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 193.142V187.51L813.45 184.689L818.335 187.505V193.142L813.45 195.958L808.565 193.142Z"/>
<path id="land_1548" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 204.686V199.054L820.189 196.233L825.074 199.049V204.686L820.189 207.503L815.299 204.686Z"/>
<path id="land_1549" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 216.225V210.599L826.923 207.777L831.814 210.599V216.225L826.923 219.047L822.038 216.225Z"/>
<path id="land_1550" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 227.775V222.137L831.22 220.726L833.662 219.321L838.553 222.143V227.769L833.662 230.591L828.777 227.775Z"/>
<path id="land_1551" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 239.313V233.681L840.402 230.865L845.287 233.681V239.313L840.402 242.135L835.511 239.313Z"/>
<path id="land_1552" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 273.945V268.308L860.614 265.492L865.505 268.313V273.945L860.614 276.761L855.729 273.945Z"/>
<path id="land_1553" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M727.703 31.5362V25.9042L732.594 23.0825L737.479 25.9042V31.5362L732.594 34.3523L727.703 31.5362Z"/>
<path id="land_1554" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M734.442 43.0804V37.4483L739.327 34.6267L744.218 37.4483V43.0804L739.327 45.9021L734.442 43.0804Z"/>
<path id="land_1555" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M741.181 54.6246V48.9925L746.067 46.1709L750.957 48.9925V54.619L746.067 57.4407L741.181 54.6246Z"/>
<path id="land_1556" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 66.1632V60.5367L752.806 57.7151L757.691 60.5367V66.1632L752.806 68.9848L747.921 66.1632Z"/>
<path id="land_1557" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 77.7127V72.0807L759.546 69.2534L764.431 72.0751V77.7127L759.546 80.5288L754.66 77.7127Z"/>
<path id="land_1558" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 89.2513V83.6192L766.285 80.7976L771.17 83.6192V89.2513L766.285 92.073L761.394 89.2513Z"/>
<path id="land_1559" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 100.795V95.1634L773.019 92.3418L777.909 95.1634V100.795L773.019 103.617L768.134 100.795Z"/>
<path id="land_1560" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 112.339V106.707L779.758 103.886L784.649 106.707V112.339L779.758 115.161L774.873 112.339Z"/>
<path id="land_1561" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 123.883V118.251L786.498 115.43L791.383 118.246V123.883L786.498 126.7L781.607 123.883Z"/>
<path id="land_1562" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 135.428V129.796L793.237 126.974L798.122 129.79V135.428L793.237 138.244L788.346 135.428Z"/>
<path id="land_1563" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 146.966V141.334L799.971 138.518L804.862 141.334V146.966L799.971 149.788L795.086 146.966Z"/>
<path id="land_1564" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 158.51V152.884L806.71 150.062L811.601 152.884V158.51L809.153 159.927L806.71 161.332L801.82 158.51Z"/>
<path id="land_1565" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 170.054V164.422L813.45 161.601L818.335 164.422V170.054L813.45 172.876L808.565 170.054Z"/>
<path id="land_1566" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 181.599V175.966L820.189 173.145L825.074 175.966V181.599L820.189 184.42L815.299 181.599Z"/>
<path id="land_1567" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 193.137V187.51L826.923 184.689L829.36 186.099L831.814 187.51V193.137L826.923 195.958L822.038 193.137Z"/>
<path id="land_1568" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 204.686V199.049L833.662 196.233L838.553 199.054V204.686L833.662 207.503L828.777 204.686Z"/>
<path id="land_1569" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 216.225V210.599L840.402 207.777L845.287 210.599V216.225L840.402 219.047L835.511 216.225Z"/>
<path id="land_1570" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 227.769V222.143L844.699 220.726L847.141 219.321L852.027 222.137V227.775L847.141 230.591L842.251 227.769Z"/>
<path id="land_1571" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M747.921 43.0804V37.4483L752.806 34.6267L757.691 37.4483V43.0804L752.806 45.8965L747.921 43.0804Z"/>
<path id="land_1572" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M754.66 54.6246V48.9925L759.546 46.1709L764.431 48.9925V54.6246L759.546 57.4407L754.66 54.6246Z"/>
<path id="land_1573" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 66.1632V60.5367L766.285 57.7151L771.17 60.5367V66.1688L766.285 68.9848L761.394 66.1632Z"/>
<path id="land_1574" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 77.7071V72.0807L773.019 69.2534L777.909 72.0807V77.7071L775.461 79.118L773.019 80.5288L768.134 77.7071Z"/>
<path id="land_1575" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 89.2513V83.6192L779.758 80.7976L784.649 83.6192V89.2513L779.758 92.073L774.873 89.2513Z"/>
<path id="land_1576" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 100.795V95.1634L786.498 92.3418L791.383 95.1634V100.795L786.498 103.617L781.607 100.795Z"/>
<path id="land_1577" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 112.339V106.707L793.237 103.886L798.122 106.702V112.339L793.237 115.161L788.346 112.339Z"/>
<path id="land_1578" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 123.883V118.246L799.971 115.43L804.862 118.246V123.883L799.971 126.7L795.086 123.883Z"/>
<path id="land_1579" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 135.422V129.796L806.71 126.974L811.601 129.796V135.422L806.71 138.244L801.82 135.422Z"/>
<path id="land_1580" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 158.51V152.884L820.189 150.062L825.074 152.878V158.516L820.189 161.332L815.299 158.51Z"/>
<path id="land_1581" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 170.054V164.422L826.923 161.601L831.814 164.422V170.054L826.923 172.87L822.038 170.054Z"/>
<path id="land_1582" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 181.598V175.966L833.662 173.15L838.553 175.966V181.598L833.662 184.42L828.777 181.598Z"/>
<path id="land_1583" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 193.137V187.51L840.402 184.689L845.287 187.51V193.137L840.402 195.958L835.511 193.137Z"/>
<path id="land_1584" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 204.681V199.054L847.141 196.233L852.027 199.049V204.686L847.141 207.503L842.251 204.681Z"/>
<path id="land_1585" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 216.225V210.593L853.875 207.777L856.312 209.188L858.766 210.593V216.225L853.875 219.047L848.99 216.225Z"/>
<path id="land_1586" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 227.775V222.137L860.614 219.315L865.505 222.143V227.769L860.614 230.591L855.729 227.775Z"/>
<path id="land_1587" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M761.394 43.0804V37.4483L766.285 34.6267L771.17 37.4483V43.0804L766.285 45.9021L761.394 43.0804Z"/>
<path id="land_1588" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M768.134 54.6246V48.9925L773.019 46.1709L777.909 48.9925V54.6246L773.019 57.4407L768.134 54.6246Z"/>
<path id="land_1589" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 66.1632V60.5367L779.758 57.7151L784.649 60.5367V66.1632L779.758 68.9848L774.873 66.1632Z"/>
<path id="land_1590" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 77.7071V72.0807L786.498 69.2534L791.383 72.0807V77.7127L786.498 80.5288L781.607 77.7071Z"/>
<path id="land_1591" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 89.2513V83.6192L793.237 80.7976L798.122 83.6192V89.2513L793.237 92.073L788.346 89.2513Z"/>
<path id="land_1592" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 100.795V95.1634L799.971 92.3418L804.862 95.1634V100.795L799.971 103.617L795.086 100.795Z"/>
<path id="land_1593" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 112.334V106.707L806.71 103.886L811.601 106.707V112.334L806.71 115.155L801.82 112.334Z"/>
<path id="land_1594" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 123.883V118.246L813.45 115.43L818.335 118.246V123.883L813.45 126.7L808.565 123.883Z"/>
<path id="land_1595" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M831.22 159.927L828.777 158.516V152.878L833.662 150.062L838.553 152.884V158.51L833.662 161.332L831.22 159.927Z"/>
<path id="land_1596" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 170.054V164.422L840.402 161.601L845.287 164.422V170.054L840.402 172.87L835.511 170.054Z"/>
<path id="land_1597" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 181.593V175.966L847.141 173.15L852.027 175.966V181.598L847.141 184.42L842.251 181.593Z"/>
<path id="land_1598" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 193.137V187.51L853.875 184.689L856.312 186.099L858.766 187.51V193.142L853.875 195.958L848.99 193.137Z"/>
<path id="land_1599" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 204.686V199.049L860.614 196.233L865.505 199.054V204.686L860.614 207.503L855.729 204.686Z"/>
<path id="land_1600" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M862.463 216.225V210.593L867.354 207.777L872.239 210.593V216.225L867.354 219.047L862.463 216.225Z"/>
<path id="land_1601" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M774.873 43.0804V37.4483L779.758 34.6267L784.649 37.4483V43.0804L779.758 45.9021L774.873 43.0804Z"/>
<path id="land_1602" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M781.607 54.6246V48.9925L786.498 46.1709L791.383 48.9925V54.6246L786.498 57.4407L781.607 54.6246Z"/>
<path id="land_1603" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 66.1632V60.5367L793.237 57.7151L798.122 60.5311V66.1688L793.237 68.9848L788.346 66.1632Z"/>
<path id="land_1604" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 77.7071V72.0807L799.971 69.2534L804.862 72.0807V77.7127L802.413 79.118L799.971 80.5288L795.086 77.7071Z"/>
<path id="land_1605" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 89.2513V83.6192L806.71 80.7976L811.601 83.6192V89.2513L806.71 92.073L801.82 89.2513Z"/>
<path id="land_1606" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 100.795V95.1634L813.45 92.3418L818.335 95.1578V100.801L813.45 103.617L808.565 100.795Z"/>
<path id="land_1607" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 112.334V106.707L820.189 103.886L825.074 106.702V112.339L820.189 115.161L815.299 112.334Z"/>
<path id="land_1608" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 158.51V152.884L847.141 150.062L852.027 152.878V158.516L847.141 161.332L842.251 158.51Z"/>
<path id="land_1609" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 170.054V164.422L853.875 161.601L858.766 164.422V170.054L853.875 172.87L848.99 170.054Z"/>
<path id="land_1610" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 181.599V175.961L860.614 173.145L865.505 175.966V181.599L860.614 184.42L855.729 181.599Z"/>
<path id="land_1611" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M862.463 193.137V187.51L867.354 184.689L872.239 187.51V193.142L867.354 195.958L862.463 193.137Z"/>
<path id="land_1612" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M869.203 204.686V199.054L874.088 196.233L878.979 199.054V204.686L874.088 207.503L869.203 204.686Z"/>
<path id="land_1613" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M788.346 43.0804V37.4483L793.237 34.6267L798.122 37.4427V43.086L793.237 45.9021L788.346 43.0804Z"/>
<path id="land_1614" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M795.086 54.6246V48.9925L799.971 46.1709L804.862 48.9925V54.6246L799.971 57.4407L795.086 54.6246Z"/>
<path id="land_1615" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 66.1632V60.5367L806.71 57.7151L811.601 60.5367V66.1632L806.71 68.9848L801.82 66.1632Z"/>
<path id="land_1616" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 77.7127V72.0807L813.45 69.2534L818.335 72.0751V77.7127L813.45 80.5288L808.565 77.7127Z"/>
<path id="land_1617" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 89.2513V83.6192L820.189 80.7976L825.074 83.6192V89.2513L820.189 92.073L815.299 89.2513Z"/>
<path id="land_1618" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 100.795V95.1634L826.923 92.3418L831.814 95.1634V100.795L826.923 103.617L822.038 100.795Z"/>
<path id="land_1619" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 112.339V106.702L833.662 103.886L838.553 106.707V112.334L833.662 115.155L828.777 112.339Z"/>
<path id="land_1620" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M801.82 43.0804V37.4483L806.71 34.6267L811.601 37.4483V43.0804L806.71 45.8965L801.82 43.0804Z"/>
<path id="land_1621" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M808.565 54.6247V48.9927L813.45 46.1654L818.335 48.9927V54.6247L813.45 57.4408L808.565 54.6247Z"/>
<path id="land_1622" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M815.299 66.1632V60.5367L820.189 57.7151L825.074 60.5311V66.1688L820.189 68.9848L815.299 66.1632Z"/>
<path id="land_1623" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 77.7071V72.0807L826.923 69.2534L831.814 72.0807V77.7071L826.923 80.5288L822.038 77.7071Z"/>
<path id="land_1624" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 89.2512V83.6191L833.662 80.803L838.553 83.6191V89.2512L833.662 92.0728L828.777 89.2512Z"/>
<path id="land_1625" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 100.795V95.1634L840.402 92.3418L845.287 95.1634V100.795L840.402 103.617L835.511 100.795Z"/>
<path id="land_1626" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 112.334V106.707L847.141 103.886L852.027 106.702V112.339L847.141 115.155L842.251 112.334Z"/>
<path id="land_1627" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M822.038 54.6246V48.9925L826.923 46.1709L831.814 48.9925V54.6246L826.923 57.4407L822.038 54.6246Z"/>
<path id="land_1628" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M828.777 66.1688V60.5311L833.662 57.7151L838.553 60.5367V66.1632L833.662 68.9848L828.777 66.1688Z"/>
<path id="land_1629" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 77.7071V72.0807L840.402 69.2534L845.287 72.0807V77.7071L840.402 80.5288L835.511 77.7071Z"/>
<path id="land_1630" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 89.2512V83.6247L847.141 80.803L852.027 83.6191V89.2512L847.141 92.0728L842.251 89.2512Z"/>
<path id="land_1631" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 100.795V95.1634L853.875 92.3418L856.312 93.7526L858.766 95.1634V100.795L853.875 103.617L848.99 100.795Z"/>
<path id="land_1632" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 112.339V106.702L860.614 103.886L865.505 106.707V112.334L860.614 115.161L855.729 112.339Z"/>
<path id="land_1633" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M835.511 54.6246V48.9925L840.402 46.1709L845.287 48.9925V54.6246L840.402 57.4407L835.511 54.6246Z"/>
<path id="land_1634" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M842.251 66.1632V60.5367L847.141 57.7151L852.027 60.5311V66.1688L847.141 68.9848L842.251 66.1632Z"/>
<path id="land_1635" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 77.7127V72.0807L853.875 69.2534L858.766 72.0807V77.7127L856.318 79.118L853.875 80.5288L848.99 77.7127Z"/>
<path id="land_1636" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 89.2513V83.6192L860.614 80.7976L865.505 83.6192V89.2513L860.614 92.073L855.729 89.2513Z"/>
<path id="land_1637" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M862.463 100.795V95.1634L867.354 92.3418L872.239 95.1634V100.795L867.354 103.617L862.463 100.795Z"/>
<path id="land_1638" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M869.203 112.334V106.707L874.088 103.886L878.979 106.707V112.334L874.088 115.161L869.203 112.334Z"/>
<path id="land_1639" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M848.99 54.6246V48.9925L853.875 46.1709L858.766 48.9925V54.6246L853.875 57.4407L848.99 54.6246Z"/>
<path id="land_1640" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M855.729 66.1688V60.5311L860.614 57.7151L865.505 60.5367V66.1632L860.614 68.9848L855.729 66.1688Z"/>
<path id="land_1641" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M862.463 77.7127V72.0807L867.354 69.2534L872.239 72.0807V77.7127L867.354 80.5288L862.463 77.7127Z"/>
<path id="land_1642" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M869.203 89.2513V83.6192L874.088 80.7976L878.979 83.6192V89.2513L874.088 92.073L869.203 89.2513Z"/>
<path id="land_1643" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M875.942 100.795V95.1634L880.828 92.3418L885.713 95.1634V100.795L880.828 103.617L875.942 100.795Z"/>
<path id="land_1644" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M862.463 54.6246V48.9925L867.354 46.1709L872.239 48.9925V54.6246L867.354 57.4407L862.463 54.6246Z"/>
<path id="land_1645" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M869.203 66.1632V60.5367L874.088 57.7151L878.979 60.5367V66.1632L874.088 68.9848L869.203 66.1632Z"/>
<path id="land_1646" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M875.942 77.7127V72.0807L880.828 69.2534L885.713 72.0807V77.7127L883.27 79.118L880.828 80.5288L875.942 77.7127Z"/>
<path id="land_1647" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M882.682 89.2512V83.6191L887.567 80.803L892.452 83.6191V89.2512L887.567 92.0728L882.682 89.2512Z"/>
<path id="land_1648" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M889.416 100.795V95.1634L894.306 92.3418L899.191 95.1634V100.795L894.306 103.617L889.416 100.795Z"/>
<path id="land_1649" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M902.895 123.883V118.251L907.78 115.43L912.67 118.246V123.883L907.78 126.7L902.895 123.883Z"/>
<path id="land_1650" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M909.634 135.428V129.79L914.519 126.974L919.404 129.79V135.428L914.519 138.244L909.634 135.428Z"/>
<path id="land_1651" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M916.368 146.966V141.334L921.258 138.518L926.144 141.334V146.966L921.253 149.788L916.368 146.966Z"/>
<path id="land_1652" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M923.107 158.51V152.884L927.998 150.062L932.883 152.878V158.51L927.998 161.332L923.107 158.51Z"/>
<path id="land_1653" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M882.682 66.1688V60.5367L887.567 57.7151L892.452 60.5367V66.1632L887.567 68.9848L882.682 66.1688Z"/>
<path id="land_1654" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M891.869 79.118L889.416 77.7127V72.0807L894.306 69.2534L899.191 72.0807V77.7071L894.306 80.5288L891.869 79.118Z"/>
<path id="land_1655" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M896.155 89.2513V83.6192L901.04 80.7976L905.931 83.6192V89.2513L901.04 92.073L896.155 89.2513Z"/>
<path id="land_1656" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M909.634 112.339V106.702L914.519 103.886L919.404 106.707V112.339L914.519 115.155L909.634 112.339Z"/>
<path id="land_1657" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M916.368 123.883V118.251L921.258 115.43L926.144 118.251V123.883L921.253 126.7L916.368 123.883Z"/>
<path id="land_1658" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M923.107 135.422V129.796L927.998 126.974L932.883 129.796V135.428L927.998 138.244L923.107 135.422Z"/>
<path id="land_1659" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M929.847 146.966V141.334L934.732 138.518L939.617 141.334V146.966L934.732 149.788L929.847 146.966Z"/>
<path id="land_1660" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M936.58 158.51V152.878L941.471 150.062L946.356 152.878V158.51L941.471 161.332L936.58 158.51Z"/>
<path id="land_1661" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M943.32 170.054V164.422L948.211 161.601L953.096 164.422V170.054L948.211 172.876L943.32 170.054Z"/>
<path id="land_1662" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M889.416 54.6246V48.9925L894.306 46.1709L899.191 48.9925V54.6246L894.306 57.4407L889.416 54.6246Z"/>
<path id="land_1663" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M896.155 66.1632V60.5367L901.04 57.7151L905.931 60.5367V66.1688L901.04 68.9848L896.155 66.1632Z"/>
<path id="land_1664" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M902.895 77.7071V72.0807L907.78 69.2534L912.67 72.0807V77.7127L907.78 80.5288L902.895 77.7071Z"/>
<path id="land_1665" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M909.634 89.2513V83.6192L914.519 80.7976L919.404 83.6192V89.2513L914.519 92.073L909.634 89.2513Z"/>
<path id="land_1666" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M916.368 100.795V95.1634L921.258 92.3418L926.144 95.1634V100.795L921.258 103.617L916.368 100.795Z"/>
<path id="land_1667" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M923.107 112.334V106.707L927.998 103.886L932.883 106.707V112.339L927.998 115.155L923.107 112.334Z"/>
<path id="land_1668" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M929.847 123.883V118.246L934.732 115.43L939.617 118.246V123.883L934.732 126.7L929.847 123.883Z"/>
<path id="land_1669" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M936.58 135.428V129.796L941.471 126.974L946.356 129.796V135.428L941.471 138.244L936.58 135.428Z"/>
<path id="land_1670" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M943.32 146.966V141.334L948.211 138.518L953.096 141.334V146.966L948.211 149.788L943.32 146.966Z"/>
<path id="land_1671" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M902.895 54.6246V48.9925L907.78 46.1709L912.67 48.9925V54.6246L907.78 57.4407L902.895 54.6246Z"/>
<path id="land_1672" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M909.634 66.1688V60.5311L914.519 57.7151L919.404 60.5367V66.1688L914.519 68.9848L909.634 66.1688Z"/>
<path id="land_1673" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M916.368 77.7071V72.0807L921.258 69.2534L926.144 72.0807V77.7071L921.253 80.5288L916.368 77.7071Z"/>
<path id="land_1674" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M923.107 89.2512V83.6191L927.998 80.803L932.883 83.6191V89.2512L927.998 92.0728L923.107 89.2512Z"/>
<path id="land_1675" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M929.847 100.795V95.1634L934.732 92.3418L939.617 95.1578V100.801L934.732 103.617L929.847 100.795Z"/>
<path id="land_1676" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M936.58 112.339V106.707L941.471 103.886L946.356 106.707V112.339L941.471 115.155L936.58 112.339Z"/>
<path id="land_1677" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M916.368 54.6246V48.9925L921.258 46.1709L926.144 48.9925V54.619L921.258 57.4407L916.368 54.6246Z"/>
<path id="land_1678" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M923.107 66.1632V60.5367L927.998 57.7151L932.883 60.5367V66.1632L927.998 68.9848L923.107 66.1632Z"/>
<path id="land_1679" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M929.847 77.7071V72.0807L934.732 69.2534L939.617 72.0751V77.7127L934.732 80.5288L929.847 77.7071Z"/>
<path id="land_1680" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M936.58 89.2512V83.6191L941.471 80.803L946.356 83.6191V89.2512L941.471 92.0728L936.58 89.2512Z"/>
<path id="land_1681" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M943.32 100.795V95.1634L948.211 92.3418L953.096 95.1634V100.795L948.211 103.617L943.32 100.795Z"/>
<path id="land_1682" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M950.054 112.334V106.707L954.95 103.886L959.835 106.707V112.339L954.95 115.161L950.054 112.334Z"/>
<path id="land_1683" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M929.847 54.6246V48.9925L934.732 46.1709L939.617 48.9925V54.6246L934.732 57.4407L929.847 54.6246Z"/>
<path id="land_1684" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M936.58 66.1632V60.5367L941.471 57.7151L946.356 60.5367V66.1632L941.471 68.9848L936.58 66.1632Z"/>
<path id="land_1685" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M943.32 77.7071V72.0807L948.211 69.2534L953.096 72.0807V77.7071L948.211 80.5288L943.32 77.7071Z"/>
<path id="land_1686" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M950.054 89.2513V83.6192L954.95 80.7976L959.835 83.6192V89.2513L954.95 92.073L950.054 89.2513Z"/>
<path id="land_1687" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M956.793 100.795V95.1634L961.684 92.3418L966.575 95.1634V100.795L961.684 103.617L956.793 100.795Z"/>
<path id="land_1688" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M950.054 66.1632V60.5367L954.95 57.7151L959.835 60.5367V66.1688L954.95 68.9848L950.054 66.1632Z"/>
<path id="land_1689" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M956.793 77.7071V72.0807L961.684 69.2534L966.575 72.0807V77.7071L961.684 80.5288L956.793 77.7071Z"/>
<path id="land_1690" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M963.538 89.2513V83.6192L968.423 80.7976L973.314 83.6192V89.2513L968.423 92.073L963.538 89.2513Z"/>
<path id="land_1691" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M970.272 100.795V95.1634L975.163 92.3418L980.048 95.1634V100.795L975.163 103.617L970.272 100.795Z"/>
<path id="land_1692" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M963.538 66.1688V60.5311L968.423 57.7151L973.314 60.5367V66.1632L968.423 68.9848L963.538 66.1688Z"/>
<path id="land_1693" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M977.006 66.1632V60.5367L981.897 57.7151L986.787 60.5367V66.1632L981.897 68.9848L977.006 66.1632Z"/>
<path id="land_1694" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M983.745 77.7071V72.0807L988.636 69.2534L993.527 72.0807V77.7071L988.636 80.5288L983.745 77.7071Z"/>
<path id="land_1695" class="land" ref="landRef" fill-rule="evenodd" clip-rule="evenodd" d="M997.224 77.7071V72.0807L1002.11 69.2534L1007 72.0807V77.7071L1004.55 79.118L1002.11 80.5288L997.224 77.7071Z"/>
                    </svg>

                    <div
                        v-if="mapPopup.visible"
                        class="map-popup-hover-zone"
                        :style="{ left: mapPopup.x - 14 + 'px', top: mapPopup.y - 14 + 'px' }"
                        @mouseenter="cancelHideMapPopup"
                        @mouseleave="scheduleHideMapPopup"
                        @click="openMapDetailsPopup()"
                        role="button"
                        tabindex="0"
                        @keydown.enter.prevent="openMapDetailsPopup()"
                        @keydown.space.prevent="openMapDetailsPopup()"
                    >
                        <div class="map-popup map-popup--hover map-popup-trigger">
                            <div class="map-popup-hover-title-row">
                                <img
                                    v-if="mapPopup.hoverImage"
                                    :src="mapPopup.hoverImage"
                                    alt="Флаг"
                                    class="map-popup-flag"
                                />
                                <span v-if="mapPopup.title" class="map-popup-title-button">
                                    {{ mapPopup.title }}
                                </span>
                            </div>
                            <p class="map-popup-count">{{ mapPopup.projectCountLabel }}</p>
                        </div>
                    </div>

                    <div
                        v-if="mapDetailsPopup.visible"
                        class="map-popup-overlay"
                        @click.self="closeMapDetailsPopup"
                    >
                        <div class="map-popup-dialog">
                            <div class="map-popup-dialog-header">
                                <p class="map-popup-dialog-title">{{ mapDetailsPopup.title }}</p>
                                <button
                                    type="button"
                                    class="map-popup-close"
                                    @click="closeMapDetailsPopup"
                                    aria-label="Закрыть popup"
                                >
                                    ×
                                </button>
                            </div>

                            <div class="map-popup-projects-list">
                                <div
                                    v-for="project in mapDetailsPopup.projects"
                                    :key="project.id"
                                    class="map-popup-project-row"
                                >
                                    <Link
                                        :href="route('portfolio.show', project.slug)"
                                        class="map-popup-logo-link"
                                        :title="project.title"
                                    >
                                        <img
                                            :src="project.logo_image"
                                            :alt="project.title"
                                            class="map-popup-logo"
                                        />
                                    </Link>

                                    <div class="map-popup-properties" v-if="project.properties?.length">
                                        <span
                                            v-for="property in project.properties"
                                            :key="`${project.id}-${property}`"
                                            class="map-popup-property-tag"
                                        >
                                            {{ property }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>


            </section>

            <section class="mx-auto max-w-6xl px-4 pb-28 sm:px-6 lg:px-8" id="projects">
                <div class=" flex justify-center gap-8 text-2xl font-semibold sm:text-[32px] country-switcher mb-[50px]">
                    <button
                        type="button"
                        class="country-switcher-button"
                        :class="activeCountry === 'russia' ? 'is-active' : 'is-inactive'"
                        @click="activeCountry = 'russia'"
                    >
                        Россия
                    </button>
                    <button
                        type="button"
                        class="country-switcher-button"
                        :class="activeCountry === 'world' ? 'is-active' : 'is-inactive'"
                        @click="activeCountry = 'world'"
                    >
                        Европа
                    </button>
                </div>
                <div ref="projectsContainerRef" class="grid gap-x-6 gap-y-8 md:grid-cols-2 project-cards" @mouseleave="handleProjectsGridLeave">
                    <transition name="project-cursor">
                        <div
                            v-if="showProjectCursor"
                            class="project-cursor"
                            :style="{
                                left: projectCursorX + 'px',
                                top: projectCursorY + 'px'
                            }"
                            aria-hidden="true"
                        >
                            <span class="project-cursor-icon">
                                <svg class="h-full w-full block fill-[#000000]"><use xlink:href="/images/sprite.svg#arrow"></use></svg>
                            </span>
                        </div>
                    </transition>
                    <article
                        v-for="project in filteredProjectsCards"
                        :key="project.id"
                        class="project-card rounded-xl"
                        @mouseenter="handleProjectCardEnter"
                        @mousemove="handleProjectCardMove"
                        @mouseleave="handleProjectCardLeave"
                    >
                        <Link :href="route('portfolio.show', project.slug)" class="project-card-link block">
                            <div class="card-preview aspect-[16/9] overflow-hidden rounded-xl bg-gray-100">
                                <img
                                    v-if="project.desktop_mockup_image || project.thumbnail_url"
                                    :src="project.desktop_mockup_image || project.thumbnail_url"
                                    :alt="project.title"
                                    class="card-preview-image"
                                    @load="handleProjectCardImageLoad"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 text-sm font-medium text-gray-600">
                                    {{ project.title }}
                                </div>
                            </div>
                            <h3 class="project-card-title mt-6 text-2xl font-semibold leading-tight sm:text-[32px]">{{ project.title }}</h3>
                        </Link>
                    </article>
                    <div v-if="filteredProjectsCards.length === 0" class="md:col-span-2 rounded-xl border border-dashed border-gray-300 p-10 text-center text-base text-gray-500">
                        Для выбранного региона проекты пока не добавлены.
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
.land {
    fill: #777777;
}

.land-interactive {
    cursor: pointer;
    transition: transform 0.18s ease;
    transform-box: fill-box;
    transform-origin: center;
}

.land-interactive:hover {
    transform: scale(1.08);
}

.land-with-objects--low {
    fill: #FBC349;
}

.land-with-objects--midle {
    fill: #FB8749;
}

.land-with-objects--hight {
    fill: #FB6149;
}

.map-popup {
    position: absolute;
    z-index: 30;
    display: flex;
    align-items: center;
    gap: 30px;
    padding: 30px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(4px);
}

.map-popup-hover-zone {
    position: absolute;
    z-index: 30;
    padding: 14px;
    cursor: pointer;
}

.map-popup--hover {
    position: relative;
    min-width: 160px;
    padding: 30x;
    gap: 30px;
}

.map-popup-hover-title-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.map-popup-flag {
    width: 22px;
    height: 20px;
    border-radius: 9999px;
    object-fit: inherit;
    border: 1px solid rgba(0, 0, 0, 0.12);
    flex-shrink: 0;
}

.map-popup-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    color: #333333;
}

.map-popup-title-button {
    display: block;
    font-size: 24px;
    font-weight: 700;
    line-height: 1.2;
    color: #333333;
    text-align: left;
}

.map-popup-trigger {
    border: 1px solid rgba(0, 0, 0, 0.08);
    cursor: pointer;
    text-align: left;
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.map-popup-trigger:hover {
    box-shadow: 0 14px 34px rgba(0, 0, 0, 0.18);
}


.map-popup-count {
    margin: 0;
    font-size: 18px;
    line-height: 1.3;
    color: #4b5563;
}

.map-popup-logos {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: wrap;
}

.map-popup-projects-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.map-popup-project-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.map-popup-properties {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.map-popup-property-tag {
    display: inline-flex;
    align-items: center;
    padding: 3px 8px;
    border-radius: 9999px;
    background: #f3f4f6;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.2;
    color: #374151;
}

.map-popup-overlay {
    position: fixed;
    inset: 0;
    z-index: 60;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(17, 24, 39, 0.45);
}

.map-popup-dialog {
    width: min(100%, 420px);
    padding: 30px;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 24px 70px rgba(0, 0, 0, 0.22);
}

.map-popup-dialog-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 16px;
}

.map-popup-dialog-title {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    line-height: 1.2;
    color: #111827;
}

.map-popup-close {
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 9999px;
    background: #f3f4f6;
    color: #111827;
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    transition: background 0.15s ease, transform 0.15s ease;
}

.map-popup-close:hover {
    background: #e5e7eb;
    transform: scale(1.04);
}

.map-popup-logo-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    padding: 6px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #ffffff;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.map-popup-logo-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.map-popup-logo {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.project-card {
    perspective: 1600px;
    position: relative;
    z-index: 0;
}

.project-card.is-hovered {
    z-index: 10;
}

.project-card-link {
    display: block;
    transform-origin: center center;
    transform-style: preserve-3d;
    transition: transform 1.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.project-card-title {
    transition: transform 1.4s cubic-bezier(0.22, 1, 0.36, 1), color 0.35s ease;
}

.project-card.is-hovered .project-card-link {
    transform: perspective(1600px) rotateX(7deg) rotateY(-10deg) scale(1.04);
}

.project-card.is-hovered .project-card-title {
    transform: translate3d(8px, -2px, 32px);
    color: #111827;
}

.card-preview {
    position: relative;
    --card-preview-shift: 0px;
    transform-origin: center center;
    transition: box-shadow 1.4s cubic-bezier(0.22, 1, 0.36, 1), transform 1.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.card-preview-image {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: auto;
    transform: translate3d(0, 0, 0) scale(1);
    transform-origin: top center;
    will-change: transform;
    transition: transform 10.5s cubic-bezier(0.22, 1, 0.36, 1);
}

.project-card.is-hovered .card-preview {
    box-shadow: 0 30px 80px rgba(17, 24, 39, 0.18);
    transform: translateZ(24px);
}

.project-card.is-hovered .card-preview-image {
    transform: translate3d(0, var(--card-preview-shift), 0) scale(1.5);
}

.project-cards,
.project-cards .project-card,
.project-cards .project-card * {
    cursor: none !important;
}

.project-cards {
    position: relative;
}

.project-cursor {
    position: absolute;
    width: 54px;
    height: 54px;
    border-radius: 9999px;
    background: rgba(229, 231, 235, 0.95);
    box-shadow: 0 12px 30px rgba(17, 24, 39, 0.16);
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    transform: translate(-50%, -50%);
    z-index: 40;
}

.project-cursor-icon {
    display: block;
    width: 12px;
    height: 13px;
}

.project-cursor-enter-active,
.project-cursor-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.project-cursor-enter-from,
.project-cursor-leave-to {
    opacity: 0;
    transform: translate(-50%, -50%) scale(0.72);
}

.project-cursor-enter-to,
.project-cursor-leave-from {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.country-switcher-button {
    border: 0;
    padding: 0;
    background: transparent;
    font: inherit;
    cursor: pointer;
    transition: color 0.2s ease;
}

.country-switcher-button.is-active {
    color: #111827;
}

.country-switcher-button.is-inactive {
    color: #9ca3af;
}

.scroll-image {
    will-change: transform;
}

.scroll-image-mobile {
    will-change: transform;
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
    display: flex;
    align-items: center;
    justify-content: center;
}

.services-spotlight-icon {
    display: block;
    width: 12px;
    height: 13px;

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

.service-card-surface {
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: #262626;
    z-index: 1;
    pointer-events: none;
    transform-origin: center center;
    transform-style: preserve-3d;
    transition: transform 1.4s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 1.4s cubic-bezier(0.22, 1, 0.36, 1);
}

.service-card.is-hovered .service-card-surface {
    z-index: 15;
    transform: perspective(1600px) rotateX(7deg) rotateY(-10deg) scale(1.04);
    box-shadow: 0 30px 80px rgba(17, 24, 39, 0.18);
}


.service-card h3,
.service-card p {
    color: #ffffff;
    mix-blend-mode: difference;
    position: relative;
    z-index: 30;
    transform: translate3d(0, 0, 0);
    transition: transform 1.4s cubic-bezier(0.22, 1, 0.36, 1), color 0.35s ease;
}

.service-card.is-hovered h3 {
    transform: translate3d(8px, -2px, 32px);
}

.service-card.is-hovered p {
    transform: translate3d(8px, 0, 24px);
}
</style>
