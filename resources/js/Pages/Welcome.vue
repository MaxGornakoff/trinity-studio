<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import WelcomeMapSection from '../Components/WelcomeMapSection.vue';

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
const isAnimActive = ref(false);
const isAnimOriginVisible = ref(false);
const isAnimOriginRingVisible = ref(false);
const isAnimOriginOuterRingVisible = ref(false);
const isAnimClosing = ref(false);

const TRANSITION_MS = 600;
const PRELOAD_MAX_WAIT_MS = 350;
const HOVER_SCROLL_DURATION_MS = 11000;
const SCROLL_RESET_DURATION_MS = 1000;
const ANIM_STAGE_ONE_MS = 200;
const ANIM_STAGE_TWO_MS = 100;
const ANIM_CLOSE_STAGE_ONE_MS = 100;
const ANIM_CLOSE_STAGE_TWO_MS = 200;
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
    interactionMenuItems: {
        type: Array,
        default: () => [],
    },
});

const activeCountry = ref('world');

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

const handleResize = () => {
    calculateScrollOffset();
    calculateScrollOffsetMobile();
};

const getInteractionMenuItemStyle = (index, total) => ({
    '--interaction-angle': `${-90 + (360 / total) * index}deg`,
    '--interaction-index': `${index}`,
    '--interaction-count': `${total}`,
});

const topLevelInteractionMenuItems = computed(() => props.interactionMenuItems ?? []);

let animStageOneTimer = null;
let animCloseTimer = null;
let animStageTwoTimer = null;
let animCloseStageOneTimer = null;
let animCloseStageTwoTimer = null;

const clearAnimStageOneTimer = () => {
    if (animStageOneTimer) {
        clearTimeout(animStageOneTimer);
        animStageOneTimer = null;
    }
};

const clearAnimCloseTimer = () => {
    if (animCloseTimer) {
        clearTimeout(animCloseTimer);
        animCloseTimer = null;
    }
};

const clearAnimCloseStageOneTimer = () => {
    if (animCloseStageOneTimer) {
        clearTimeout(animCloseStageOneTimer);
        animCloseStageOneTimer = null;
    }
};

const clearAnimCloseStageTwoTimer = () => {
    if (animCloseStageTwoTimer) {
        clearTimeout(animCloseStageTwoTimer);
        animCloseStageTwoTimer = null;
    }
};

const clearAnimStageTwoTimer = () => {
    if (animStageTwoTimer) {
        clearTimeout(animStageTwoTimer);
        animStageTwoTimer = null;
    }
};

const handleStartActionClick = () => {
    clearAnimStageOneTimer();
    clearAnimCloseTimer();
    clearAnimStageTwoTimer();
    clearAnimCloseStageOneTimer();
    clearAnimCloseStageTwoTimer();

    if (isAnimActive.value) {
        isAnimClosing.value = true;
        isAnimOriginOuterRingVisible.value = false;

        animCloseStageOneTimer = setTimeout(() => {
            if (isAnimActive.value && isAnimClosing.value) {
                isAnimOriginRingVisible.value = false;

                animCloseStageTwoTimer = setTimeout(() => {
                    if (isAnimActive.value && isAnimClosing.value) {
                        isAnimOriginVisible.value = false;

                        animCloseTimer = setTimeout(() => {
                            isAnimActive.value = false;
                            isAnimClosing.value = false;
                            animCloseTimer = null;
                        }, ANIM_CLOSE_STAGE_TWO_MS);
                    }

                    animCloseStageTwoTimer = null;
                }, ANIM_STAGE_TWO_MS);
            }

            animCloseStageOneTimer = null;
        }, ANIM_CLOSE_STAGE_ONE_MS);

        return;
    }

    isAnimClosing.value = false;
    isAnimActive.value = true;
    isAnimOriginRingVisible.value = false;
    isAnimOriginOuterRingVisible.value = false;
    isAnimOriginVisible.value = false;

    animStageOneTimer = setTimeout(() => {
        if (isAnimActive.value && !isAnimClosing.value) {
            isAnimOriginVisible.value = true;

            animStageTwoTimer = setTimeout(() => {
                if (isAnimActive.value && !isAnimClosing.value && isAnimOriginVisible.value) {
                    isAnimOriginRingVisible.value = true;
                    isAnimOriginOuterRingVisible.value = true;
                }
                animStageTwoTimer = null;
            }, ANIM_STAGE_TWO_MS);
        }
        animStageOneTimer = null;
    }, ANIM_STAGE_ONE_MS);
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

onUnmounted(() => {
    clearAnimStageOneTimer();
    clearAnimCloseTimer();
    clearAnimStageTwoTimer();
    clearAnimCloseStageOneTimer();
    clearAnimCloseStageTwoTimer();
    window.removeEventListener('resize', handleResize);
});
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

            <WelcomeMapSection
                :active-country="activeCountry"
                :map-projects="props.mapProjects"
                :map-popup-locations="props.mapPopupLocations"
            />

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

            <section class="mx-auto max-w-6xl px-4 pb-32 sm:px-6 lg:px-8" id="interaction">
                <div class="grid gap-8 sm:grid-cols-2 sm:items-end">
                    <h2 class="text-3xl font-bold leading-tight tracking-[-0.02em] sm:text-5xl">Взаимодействие</h2>
                    <p class="max-w-md text-[13px] leading-relaxed text-gray-500 sm:justify-self-end">
                        Мы берём рутину на себя — сбор данных, подготовку, организацию. Вы фокусируетесь на главном,
                        а мы доводим процесс до результата.
                    </p>
                </div>

                <div id="scheme">
                    <div class="start relative h-[630px] w-[630px] shrink-0 rounded-full m-auto">
                        <svg class="start-border" viewBox="0 0 630 630" aria-hidden="true">
                            <circle cx="315" cy="315" r="314.5" />
                        </svg>
                        <svg class="start-border start-border--inner" viewBox="0 0 380 380" aria-hidden="true">
                            <circle cx="190" cy="190" r="189.5" />
                        </svg>
                        <svg class="start-origin-dot-ring" :class="{ 'start-origin-dot-ring--visible': isAnimOriginOuterRingVisible }" viewBox="0 0 292 292" aria-hidden="true">
                            <circle cx="146" cy="146" r="145.5" />
                        </svg>
                        <div class="start-origin-ring start-origin-ring--outer" :class="{ 'start-origin-ring--visible': isAnimOriginOuterRingVisible }" aria-hidden="true"></div>
                        <div class="start-origin-ring" :class="{ 'start-origin-ring--visible': isAnimOriginRingVisible }" aria-hidden="true"></div>
                        <div class="start-origin-circle" :class="{ 'start-origin-circle--visible': isAnimOriginVisible }" aria-hidden="true"></div>
                        <div class="road-line" :class="{ 'road-line--expanded': isAnimActive }">
                            <button type="button" class="road-line-action road-line-action--start flex items-center" @click="handleStartActionClick">
                                <span class="road-line-action-label text-[32px] items-center relative h-9 inline-flex font-semibold">Старт</span>
                                <span class="road-line-cap road-line-cap--start" :class="{ 'road-line-cap--anim-active': isAnimActive }" aria-hidden="true"></span>
                            </button>
                            <span class="road-line-cap road-line-cap--end" aria-hidden="true"></span>
                            <div class="road-line-label road-line-label--finish cursor-pointer text-[32px] items-center relative h-9 inline-flex font-semibold">Финиш</div>
                        </div>
                        <div class="interaction-menu" :class="{ 'interaction-menu--visible': isAnimOriginOuterRingVisible }">
                            <button
                                v-for="(item, index) in topLevelInteractionMenuItems"
                                :key="item.id ?? item.slug ?? item.title"
                                type="button"
                                class="interaction-menu-item"
                                :style="getInteractionMenuItemStyle(index, topLevelInteractionMenuItems.length)"
                            >
                                <span class="interaction-menu-item-label">{{ item.title }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>

<style scoped>
.start {
    position: relative;
}

.start-border {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.start-border--inner {
    inset: 50%;
    width: 380px;
    height: 380px;
    transform: translate(-50%, -50%);
}

.start-border circle {
    fill: none;
    stroke: #333333;
    stroke-width: 1;
    stroke-linecap: round;
    stroke-dasharray: 0 5;
}

.start > * {

    z-index: 1;
}

.start-origin-ring {
    position: absolute;
    top: 50%;
    left: 1px;
    width: 136px;
    height: 136px;
    border: 2px solid #333333;
    border-radius: 9999px;
    transform: translate(-50%, -50%) scale(0.9);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.start-origin-dot-ring {
    position: absolute;
    top: 50%;
    left: 1px;
    width: 292px;
    height: 292px;
    transform: translate(-50%, -50%) scale(0.9);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.start-origin-dot-ring circle {
    fill: none;
    stroke: #333333;
    stroke-width: 1;
    stroke-linecap: round;
    stroke-dasharray: 0 5;
}

.start-origin-dot-ring--visible {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.interaction-menu {
    position: absolute;
    top: 50%;
    left: 1px;
    width: 0;
    height: 0;
    z-index: 5;
    overflow: visible;
    pointer-events: none;
    transform: translate(-50%, -50%);
}

.interaction-menu--visible {
    pointer-events: auto;
}

.interaction-menu-item {
    position: absolute;
    top: 0;
    left: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 0;
    border: 0;
    background: transparent;
    font: inherit;
    color: inherit;
    cursor: pointer;
    opacity: 0;
    transform:
        rotate(var(--interaction-angle))
        translateX(148px)
        rotate(calc(var(--interaction-angle) * -1))
        translate(-50%, -50%)
        scale(0.72);
    transition: opacity 0.2s ease, transform 0.45s cubic-bezier(0.22, 1, 0.36, 1);
    transition-delay: calc((var(--interaction-count) - var(--interaction-index) - 1) * 40ms);
}

.interaction-menu--visible .interaction-menu-item {
    opacity: 1;
    transform:
        rotate(var(--interaction-angle))
        translateX(148px)
        rotate(calc(var(--interaction-angle) * -1))
        translate(-50%, -50%)
        scale(1);
    transition-delay: calc(40ms + var(--interaction-index) * 75ms);
}

.interaction-menu-item-label {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 116px;
    min-height: 36px;
    padding: 4px 18px;
    border-radius: 9999px;
    background: #333333;
    backdrop-filter: blur(10px);
    font-size: 15px;
    font-weight: 500;
    line-height: 1;
    color: #ffffff;
    white-space: nowrap;
    transition: transform 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
}

.interaction-menu-item:hover .interaction-menu-item-label {
    transform: translateY(-2px);

}

.start-origin-ring--outer {
    width: 162px;
    height: 162px;
    border-width: 1px;
}

.start-origin-ring--visible {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.start-origin-circle {
    position: absolute;
    top: 50%;
    left: -40px;
    width: 82px;
    height: 82px;
    border-radius: 9999px;
    background: #333333;
    transform: translateY(-50%) scale(0.82);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.start-origin-circle--visible {
    opacity: 1;
    transform: translateY(-50%) scale(1);
}

.road-line {
    position: absolute;
    top: 50%;
    left: 6px;
    right: 0;
    bottom: 50%;
    transition: left 0.2s ease;
}

.road-line--expanded {
    left: -170px;
}

.road-line::before {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 5px;
    transform: translateY(-50%);
    background-image: radial-gradient(circle at center, #333333 0.5px, transparent 0.75px);
    background-repeat: repeat-x;
    background-position: left center;
    background-size: 5px 5px;
    pointer-events: none;
}

.road-line > * {
    position: relative;
    z-index: 1;
}

.road-line-action {
    position: absolute;
    top: 50%;
    display: inline-flex;
    align-items: center;
    padding: 0;
    margin: 0;
    border: 0;
    background: transparent;
    appearance: none;
    color: inherit;
    font: inherit;
    transform: translateY(-50%);
    cursor: pointer;
}

.road-line-action--start {
    right: 100%;
    padding-right: 30px;

}

.road-line-action--start:hover {
    .road-line-action-label{
        transform: scale(1.1);
        transition: all 0.2s;
    }
}

.road-line-action--start:not(:hover) {
    .road-line-action-label{

        transition: all 0.2s;
    }
}

.road-line-action-label {
    white-space: nowrap;
}

.road-line-label {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    white-space: nowrap;
}

.road-line-label--finish {
    right: -145px;
}

.road-line-cap {
    display: block;
    width: 10px;
    height: 10px;
    border-radius: 9999px;
    background: #333333;
    pointer-events: none;
    flex-shrink: 0;
}

.road-line-cap--start {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    animation: startCapPulse 2.4s ease-in-out infinite;
    transform-origin: center center;
    will-change: transform;
    z-index: 1;
}

.road-line-cap--start.road-line-cap--anim-active {
    animation: none;
    transform: translateY(-50%) scale(1);
}

.road-line-cap--start::before,
.road-line-cap--start::after {
    content: '';
    display: block;
    position: absolute;
    left: 50%;
    top: 50%;
    width: 10px;
    height: 10px;
    border: 1px solid rgba(51, 51, 51, 0.7);
    border-radius: 9999px;
    transform: translate(-50%, -50%) scale(1);
    transform-origin: center center;
    will-change: transform, opacity;
    opacity: 0;
}

.road-line-cap--start::before {
    animation: startCapWave 2.4s ease-out infinite;
}

.road-line-cap--start::after {
    animation: startCapWave 2.4s ease-out infinite 0.8s;
}

.road-line-cap--start.road-line-cap--anim-active::before,
.road-line-cap--start.road-line-cap--anim-active::after {
    animation: none;
    opacity: 0;
}

.road-line-cap--end {
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    z-index: 0;
}

@keyframes startCapPulse {
    0%,
    100% {
        transform: translateY(-50%) scale(1);
    }
    18% {
        transform: translateY(-50%) scale(1.12);
    }
    30% {
        transform: translateY(-50%) scale(1);
    }
}

@keyframes startCapWave {
    0% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(1);
    }
    12% {
        opacity: 0.55;
        transform: translate(-50%, -50%) scale(1.4);
    }
    100% {
        opacity: 0;
        transform: translate(-50%, -50%) scale(3.4);
    }
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
