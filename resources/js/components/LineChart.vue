<script setup>
import { computed, ref } from 'vue';
import { formatChartDate, formatChartDateLong, formatNumber } from '../support/analytics';
import Spinner from './Spinner.vue';

let uid = 0;

const props = defineProps({
    points: {
        type: Array,
        default: () => [],
    },
    secondary: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: 'Seitenaufrufe',
    },
    secondaryLabel: {
        type: String,
        default: 'Besuche',
    },
    unit: {
        type: String,
        default: 'day',
    },
    height: {
        type: String,
        default: '12rem',
    },
});

const PAD_TOP = 8;
const PAD_BOTTOM = 6;
const gradientId = `umami-chart-gradient-${++uid}`;
const hoverIndex = ref(null);

const primary = computed(() => props.points.map((point) => ({
    label: point.label ?? '',
    value: Number(point.value || 0),
})));

const secondarySeries = computed(() => props.secondary.map((point) => ({
    label: point.label ?? '',
    value: Number(point.value || 0),
})));

const hasSecondary = computed(() => secondarySeries.value.some((point) => point.value > 0));
const count = computed(() => primary.value.length);
const maxValue = computed(() => Math.max(
    1,
    ...primary.value.map((point) => point.value),
    ...secondarySeries.value.map((point) => point.value),
));

function px(index) {
    return count.value <= 1 ? 0 : (index / (count.value - 1)) * 100;
}

function py(value) {
    const usable = 100 - PAD_TOP - PAD_BOTTOM;

    return PAD_TOP + (1 - value / maxValue.value) * usable;
}

function linePath(series) {
    if (!series.length) {
        return '';
    }

    if (series.length === 1) {
        return `M 0 ${py(series[0].value).toFixed(2)} L 100 ${py(series[0].value).toFixed(2)}`;
    }

    return series
        .map((point, index) => `${index === 0 ? 'M' : 'L'} ${px(index).toFixed(2)} ${py(point.value).toFixed(2)}`)
        .join(' ');
}

const primaryPath = computed(() => linePath(primary.value));
const secondaryPath = computed(() => (hasSecondary.value ? linePath(secondarySeries.value) : ''));

const areaPath = computed(() => {
    if (!primary.value.length) {
        return '';
    }

    const base = (100 - PAD_BOTTOM).toFixed(2);
    const line = primary.value.length === 1
        ? `M 0 ${py(primary.value[0].value).toFixed(2)} L 100 ${py(primary.value[0].value).toFixed(2)}`
        : linePath(primary.value);

    return `${line} L 100 ${base} L 0 ${base} Z`;
});

const gridlines = computed(() => [0.25, 0.5, 0.75].map((fraction) => PAD_TOP + fraction * (100 - PAD_TOP - PAD_BOTTOM)));

const axisLabels = computed(() => {
    const total = primary.value.length;

    if (!total) {
        return [];
    }

    const indexes = total === 1 ? [0] : [...new Set([0, Math.floor((total - 1) / 2), total - 1])];

    return indexes.map((index) => formatChartDate(primary.value[index].label, props.unit));
});

const active = computed(() => {
    if (hoverIndex.value === null) {
        return null;
    }

    const point = primary.value[hoverIndex.value];

    if (!point) {
        return null;
    }

    return {
        index: hoverIndex.value,
        x: px(hoverIndex.value),
        primaryY: py(point.value),
        secondaryY: hasSecondary.value ? py(secondarySeries.value[hoverIndex.value]?.value || 0) : null,
        date: formatChartDateLong(point.label, props.unit),
        primary: point.value,
        secondary: hasSecondary.value ? (secondarySeries.value[hoverIndex.value]?.value ?? null) : null,
    };
});

function dotStyle(xPercent, yPercent, size) {
    return {
        left: `${xPercent}%`,
        top: `${yPercent}%`,
        width: size,
        height: size,
        transform: 'translate(-50%, -50%)',
        boxShadow: '0 0 0 2px #fff',
    };
}

const tooltipStyle = computed(() => {
    if (!active.value) {
        return {};
    }

    return {
        left: `${Math.min(88, Math.max(12, active.value.x))}%`,
        transform: 'translateX(-50%)',
    };
});

function onMove(event) {
    if (count.value <= 1) {
        return;
    }

    const rect = event.currentTarget.getBoundingClientRect();
    const ratio = Math.min(1, Math.max(0, (event.clientX - rect.left) / rect.width));

    hoverIndex.value = Math.round(ratio * (count.value - 1));
}

function onLeave() {
    hoverIndex.value = null;
}
</script>

<template>
    <div v-if="count" class="space-y-2">
        <div v-if="hasSecondary" class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-2">
                <span class="inline-block rounded-full bg-blue-500" style="width: 0.5rem; height: 0.5rem;"></span>{{ label }}
            </span>
            <span class="flex items-center gap-2">
                <span class="inline-block rounded-full bg-gray-400 dark:bg-gray-500" style="width: 0.5rem; height: 0.5rem;"></span>{{ secondaryLabel }}
            </span>
        </div>

        <div class="relative w-full" :style="{ height }" @mousemove="onMove" @mouseleave="onLeave">
            <span class="absolute right-0 top-0 z-10 text-xs text-gray-400 tabular-nums dark:text-gray-500">
                max {{ formatNumber(maxValue) }}
            </span>

            <svg
                viewBox="0 0 100 100"
                preserveAspectRatio="none"
                class="h-full w-full text-blue-500"
                style="overflow: visible;"
                role="img"
                :aria-label="label"
            >
                <defs>
                    <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="currentColor" stop-opacity="0.2" />
                        <stop offset="100%" stop-color="currentColor" stop-opacity="0" />
                    </linearGradient>
                </defs>

                <line
                    v-for="(y, index) in gridlines"
                    :key="`grid-${index}`"
                    x1="0" :y1="y" x2="100" :y2="y"
                    class="text-gray-200 dark:text-gray-700"
                    stroke="currentColor" stroke-width="1" stroke-dasharray="2 3"
                    vector-effect="non-scaling-stroke"
                />

                <path :d="areaPath" :fill="`url(#${gradientId})`" stroke="none" />

                <path
                    v-if="secondaryPath"
                    :d="secondaryPath"
                    class="text-gray-400 dark:text-gray-500"
                    fill="none" stroke="currentColor" stroke-width="1.5" stroke-dasharray="4 3"
                    stroke-linecap="round" stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                />

                <path
                    :d="primaryPath"
                    fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round"
                    vector-effect="non-scaling-stroke"
                />

                <line
                    v-if="active"
                    :x1="active.x" y1="0" :x2="active.x" :y2="100 - PAD_BOTTOM"
                    class="text-gray-300 dark:text-gray-600"
                    stroke="currentColor" stroke-width="1"
                    vector-effect="non-scaling-stroke"
                />
            </svg>

            <template v-if="active">
                <span
                    v-if="active.secondaryY !== null"
                    class="pointer-events-none absolute rounded-full bg-gray-400 dark:bg-gray-500"
                    :style="dotStyle(active.x, active.secondaryY, '0.5rem')"
                ></span>
                <span
                    class="pointer-events-none absolute rounded-full bg-blue-500"
                    :style="dotStyle(active.x, active.primaryY, '0.625rem')"
                ></span>
            </template>

            <div
                v-if="active"
                class="pointer-events-none absolute top-0 z-30 min-w-max rounded-md bg-gray-900 px-3 py-2 text-xs text-white shadow-lg dark:bg-gray-700"
                :style="tooltipStyle"
            >
                <div class="font-medium">{{ active.date }}</div>
                <div class="mt-1 flex items-center gap-2">
                    <span class="inline-block rounded-full bg-blue-500" style="width: 0.5rem; height: 0.5rem;"></span>
                    <span class="text-gray-300">{{ label }}</span>
                    <span class="ml-auto tabular-nums">{{ formatNumber(active.primary) }}</span>
                </div>
                <div v-if="active.secondary !== null" class="mt-1 flex items-center gap-2">
                    <span class="inline-block rounded-full bg-gray-400" style="width: 0.5rem; height: 0.5rem;"></span>
                    <span class="text-gray-300">{{ secondaryLabel }}</span>
                    <span class="ml-auto tabular-nums">{{ formatNumber(active.secondary) }}</span>
                </div>
            </div>
        </div>

        <div class="flex justify-between text-xs text-gray-400 tabular-nums dark:text-gray-500">
            <span v-for="(axisLabel, index) in axisLabels" :key="`axis-${index}`">{{ axisLabel }}</span>
        </div>
    </div>

    <div
        v-else-if="loading"
        class="relative flex w-full animate-pulse items-center justify-center rounded-md bg-gray-100 text-blue-500 dark:bg-gray-800"
        :style="{ height }"
    >
        <Spinner />
    </div>

    <div v-else class="flex items-center justify-center text-sm text-gray-500" :style="{ height }">
        Keine Verlaufsdaten
    </div>
</template>
