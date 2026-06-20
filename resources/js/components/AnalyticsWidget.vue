<script setup>
import { computed, getCurrentInstance, onMounted, ref } from 'vue';
import LineChart from './LineChart.vue';
import StatGrid from './StatGrid.vue';
import {
    formatNumber,
    formatPercent,
    normalizedPageviews,
    normalizedSessions,
    requestErrorMessage,
    statValue,
} from '../support/analytics';

const props = defineProps({
    title: {
        type: String,
        default: 'Analytics',
    },
    days: {
        type: Number,
        default: 30,
    },
    overviewUrl: {
        type: String,
        required: true,
    },
    indexUrl: {
        type: String,
        default: '',
    },
    umamiUrl: {
        type: String,
        default: '',
    },
    configured: {
        type: Boolean,
        default: false,
    },
    missing: {
        type: Array,
        default: () => [],
    },
    showChart: {
        type: Boolean,
        default: true,
    },
});

const loading = ref(false);
const error = ref(null);
const payload = ref(null);
const http = getCurrentInstance()?.appContext.config.globalProperties.$axios;

const stats = computed(() => payload.value?.stats || {});
const visits = computed(() => statValue(stats.value, 'visits'));
const bounces = computed(() => statValue(stats.value, 'bounces'));
const bounceRate = computed(() => (visits.value > 0 ? bounces.value / visits.value : 0));
const chartPoints = computed(() => normalizedPageviews(payload.value?.pageviews));
const chartVisits = computed(() => normalizedSessions(payload.value?.pageviews));
const chartUnit = computed(() => payload.value?.range?.unit || 'day');
const showSkeleton = computed(() => loading.value && !payload.value);

const cards = computed(() => [
    { key: 'pageviews', label: 'Seitenaufrufe', value: formatNumber(statValue(stats.value, 'pageviews')) },
    { key: 'visitors', label: 'Besucher', value: formatNumber(statValue(stats.value, 'visitors')) },
    { key: 'visits', label: 'Besuche', value: formatNumber(visits.value) },
    { key: 'bounce', label: 'Absprungrate', value: formatPercent(bounceRate.value) },
]);

const periodLabel = computed(() => `${props.days} Tage`);

function openUmami() {
    if (props.umamiUrl) {
        window.open(props.umamiUrl, '_blank', 'noopener,noreferrer');
    }
}

onMounted(() => {
    if (props.configured) {
        load();
    }
});

async function load() {
    loading.value = true;
    error.value = null;

    try {
        if (!http?.get) {
            throw new Error('Statamic HTTP client is not available.');
        }

        const response = await http.get(props.overviewUrl, { params: { days: props.days } });
        payload.value = response.data;
    } catch (requestError) {
        error.value = requestErrorMessage(requestError);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <ui-widget :title="title" icon="chart-monitoring-indicator">
        <template #actions>
            <ui-badge size="sm" :text="periodLabel" />
        </template>

        <div class="flex flex-1 flex-col px-4.5 py-5" style="gap: 1.25rem;">
            <ui-alert
                v-if="!configured"
                variant="warning"
                heading="Umami ist nicht konfiguriert"
                :text="`Fehlende Werte: ${missing.join(', ')}`"
            />

            <ui-alert v-else-if="error" variant="error" :text="error" />

            <template v-else>
                <StatGrid bare :stats="cards" :loading="showSkeleton" min-column="130px" />

                <LineChart
                    v-if="showChart"
                    :points="chartPoints"
                    :secondary="chartVisits"
                    :unit="chartUnit"
                    :loading="loading"
                    height="9rem"
                    label="Seitenaufrufe"
                    secondary-label="Besuche"
                />
            </template>
        </div>

        <template #footer>
            <div
                v-if="indexUrl || umamiUrl"
                class="flex flex-wrap items-center gap-1 border-t border-gray-200 px-3 py-2 dark:border-gray-700"
            >
                <ui-button
                    v-if="indexUrl"
                    size="sm"
                    variant="ghost"
                    icon="chart-monitoring-indicator"
                    text="Zur Analytics-Übersicht"
                    :href="indexUrl"
                />
                <ui-button
                    v-if="umamiUrl"
                    size="sm"
                    variant="subtle"
                    icon="external-link"
                    text="In Umami öffnen"
                    @click="openUmami"
                />
            </div>
        </template>
    </ui-widget>
</template>
