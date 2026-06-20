<script setup>
import { computed } from 'vue';
import { formatCountry, formatDevice, formatNumber, metricCount, metricLabel } from '../support/analytics';
import Skeleton from './Skeleton.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    items: {
        type: Array,
        default: () => [],
    },
    format: {
        type: String,
        default: '',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const formatters = {
    country: formatCountry,
    device: formatDevice,
};

const rows = computed(() => {
    const formatter = formatters[props.format];

    const items = props.items.slice(0, 8).map((item) => {
        const raw = metricLabel(item);

        return {
            label: formatter ? formatter(raw) : (raw || 'Direkt'),
            count: metricCount(item),
        };
    });

    const total = items.reduce((sum, item) => sum + item.count, 0) || 1;
    const max = Math.max(1, ...items.map((item) => item.count));

    return items.map((item) => ({
        ...item,
        fill: Math.round((item.count / max) * 100),
        share: item.count / total,
    }));
});

const shareLabel = (share) => new Intl.NumberFormat('de-DE', {
    style: 'percent',
    maximumFractionDigits: 0,
}).format(share);
</script>

<template>
    <ui-card-panel :heading="title">
        <div v-if="rows.length" class="-mx-1">
            <div
                v-for="row in rows"
                :key="row.label"
                class="relative flex items-center justify-between gap-3 overflow-hidden rounded-lg px-3 py-2.5"
            >
                <div
                    class="absolute left-0 rounded-md"
                    style="top: 0.25rem; bottom: 0.25rem; background-color: rgba(37, 99, 235, 0.09); will-change: width;"
                    :style="{ width: `${row.fill}%` }"
                ></div>
                <span class="relative min-w-0 truncate text-sm font-medium text-gray-800 dark:text-gray-100" :title="row.label">
                    {{ row.label }}
                </span>
                <span class="relative flex shrink-0 gap-2" style="align-items: baseline;">
                    <span class="text-xs text-gray-400 tabular-nums dark:text-gray-500">{{ shareLabel(row.share) }}</span>
                    <span class="text-sm font-semibold text-gray-900 tabular-nums dark:text-white">{{ formatNumber(row.count) }}</span>
                </span>
            </div>
        </div>

        <div v-else-if="loading" class="space-y-3 px-2 py-1">
            <div v-for="n in 5" :key="n" class="flex items-center justify-between gap-4">
                <Skeleton :width="`${64 - n * 7}%`" height="0.8rem" />
                <Skeleton width="2rem" height="0.8rem" />
            </div>
        </div>

        <ui-description v-else class="px-2 py-3">Keine Daten im gewählten Zeitraum</ui-description>
    </ui-card-panel>
</template>
