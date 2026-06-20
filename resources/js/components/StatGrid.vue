<script setup>
import Skeleton from './Skeleton.vue';

defineProps({
    /** @type {{ key?: string, label: string, value: string, live?: boolean }[]} */
    stats: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    /** Render just the grid without the surrounding card (e.g. inside a widget). */
    bare: {
        type: Boolean,
        default: false,
    },
    /** Minimum column width; smaller value packs more stats per row. */
    minColumn: {
        type: String,
        default: '165px',
    },
});

// Each metric gets a distinct icon + accent so the row reads as a dashboard,
// not a flat list of numbers. Accents are 500-level so they hold up on both
// light and dark backgrounds.
const META = {
    pageviews: { icon: 'eye', accent: '#3b82f6' },
    visitors: { icon: 'users', accent: '#14b8a6' },
    visits: { icon: 'cursor-click', accent: '#8b5cf6' },
    bounce: { icon: 'sign-out', accent: '#f59e0b' },
    duration: { icon: 'time-clock', accent: '#64748b' },
    active: { icon: 'pulse', accent: '#10b981' },
};

const fallback = { icon: 'chart-monitoring-indicator', accent: '#64748b' };

const meta = (stat) => META[stat.key] || fallback;

const tint = (hex) => {
    const int = parseInt(hex.slice(1), 16);

    return `rgba(${(int >> 16) & 255}, ${(int >> 8) & 255}, ${int & 255}, 0.13)`;
};
</script>

<template>
    <component :is="bare ? 'div' : 'ui-card'">
        <div class="grid" style="gap: 1.5rem 1.25rem;" :style="{ gridTemplateColumns: `repeat(auto-fit, minmax(${minColumn}, 1fr))` }">
            <div v-for="(stat, index) in stats" :key="stat.key || stat.label" class="flex min-w-0 items-center gap-3">
                <span
                    v-if="loading"
                    class="shrink-0 animate-pulse rounded-xl bg-gray-100 dark:bg-gray-800"
                    style="width: 2.5rem; height: 2.5rem;"
                ></span>
                <span
                    v-else
                    class="relative flex shrink-0 items-center justify-center rounded-xl"
                    style="width: 2.5rem; height: 2.5rem;"
                    :style="{ color: meta(stat).accent, backgroundColor: tint(meta(stat).accent) }"
                >
                    <ui-icon :name="meta(stat).icon" style="width: 1.15rem; height: 1.15rem;" />
                    <span
                        v-if="stat.live"
                        class="absolute animate-pulse rounded-full"
                        style="top: -1px; right: -1px; width: 0.5rem; height: 0.5rem; background-color: #10b981; box-shadow: 0 0 0 2px var(--color-white, #fff); will-change: opacity;"
                    ></span>
                </span>

                <div class="min-w-0">
                    <template v-if="loading">
                        <Skeleton width="70%" height="0.7rem" />
                        <Skeleton class="mt-2" width="50%" height="1.5rem" />
                    </template>
                    <template v-else>
                        <ui-text size="xs" variant="subtle" class="block truncate">{{ stat.label }}</ui-text>
                        <div class="mt-0.5 text-2xl font-semibold leading-tight tabular-nums text-gray-900 dark:text-white">
                            {{ stat.value }}
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </component>
</template>
