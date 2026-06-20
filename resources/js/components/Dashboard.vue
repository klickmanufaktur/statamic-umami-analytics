<script setup>
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue';
import LineChart from './LineChart.vue';
import MetricList from './MetricList.vue';
import StatGrid from './StatGrid.vue';
import {
    activeVisitors,
    formatDateRange,
    formatDuration,
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
    overviewUrl: {
        type: String,
        required: true,
    },
    umamiUrl: {
        type: String,
        default: '',
    },
    periods: {
        type: Array,
        default: () => [7, 30, 90],
    },
    initialDays: {
        type: Number,
        default: 30,
    },
    configured: {
        type: Boolean,
        default: false,
    },
    missing: {
        type: Array,
        default: () => [],
    },
});

const days = ref(props.initialDays);
const loading = ref(false);
const error = ref(null);
const payload = ref(null);
const configured = ref(props.configured);
const missing = ref(props.missing);
const http = getCurrentInstance()?.appContext.config.globalProperties.$axios;

const showSkeleton = computed(() => loading.value && !payload.value);

const selectedPeriod = computed({
    get: () => String(days.value),
    set: (value) => {
        if (value) {
            days.value = Number(value);
        }
    },
});

const stats = computed(() => payload.value?.stats || {});
const chartPoints = computed(() => normalizedPageviews(payload.value?.pageviews));
const chartVisits = computed(() => normalizedSessions(payload.value?.pageviews));
const chartUnit = computed(() => payload.value?.range?.unit || 'day');
const active = computed(() => activeVisitors(payload.value?.active));
const visits = computed(() => statValue(stats.value, 'visits'));
const bounces = computed(() => statValue(stats.value, 'bounces'));
const totalTime = computed(() => statValue(stats.value, 'totaltime'));
const bounceRate = computed(() => visits.value > 0 ? bounces.value / visits.value : 0);
const averageTime = computed(() => visits.value > 0 ? totalTime.value / visits.value : 0);

const metricCards = computed(() => [
    { key: 'pageviews', label: 'Seitenaufrufe', value: formatNumber(statValue(stats.value, 'pageviews')) },
    { key: 'visitors', label: 'Besucher', value: formatNumber(statValue(stats.value, 'visitors')) },
    { key: 'visits', label: 'Besuche', value: formatNumber(visits.value) },
    { key: 'bounce', label: 'Absprungrate', value: formatPercent(bounceRate.value) },
    { key: 'duration', label: 'Ø Besuchsdauer', value: formatDuration(averageTime.value) },
    { key: 'active', label: 'Jetzt aktiv', value: formatNumber(active.value), live: true },
]);

const rangeLabel = computed(() => formatDateRange(payload.value?.range));

function openUmami() {
    if (props.umamiUrl) {
        window.open(props.umamiUrl, '_blank', 'noopener,noreferrer');
    }
}

watch(days, () => {
    load();
});

onMounted(() => {
    if (configured.value) {
        load();
    }
});

async function load() {
    loading.value = true;
    error.value = null;
    // Reset so a period switch shows the same skeleton as the initial page load.
    payload.value = null;

    try {
        if (!http?.get) {
            throw new Error('Statamic HTTP client is not available.');
        }

        const response = await http.get(props.overviewUrl, {
            params: { days: days.value },
        });

        payload.value = response.data;
        configured.value = response.data.configured !== false;
        missing.value = response.data.missing || [];
    } catch (requestError) {
        configured.value = requestError.response?.data?.configured !== false;
        missing.value = requestError.response?.data?.missing || missing.value;
        error.value = requestErrorMessage(requestError);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <ui-header :title="title" icon="dashboard">
        <div class="flex items-center gap-3">
            <ui-button
                v-if="umamiUrl"
                size="sm"
                icon="external-link"
                text="In Umami öffnen"
                @click="openUmami"
            />
            <ui-toggle-group v-model="selectedPeriod" required size="sm">
                <ui-toggle-item
                    v-for="period in periods"
                    :key="period"
                    :value="String(period)"
                    :label="`${period} Tage`"
                />
            </ui-toggle-group>
        </div>
    </ui-header>

    <ui-alert
        v-if="!configured"
        variant="warning"
        heading="Umami ist nicht konfiguriert"
        :text="`Fehlende Werte: ${missing.join(', ')}`"
    />

    <ui-alert v-else-if="error" variant="error">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <ui-description>{{ error }}</ui-description>
            <ui-button size="sm" text="Neu laden" @click="load" />
        </div>
    </ui-alert>

    <div v-else class="space-y-6">
        <StatGrid :stats="metricCards" :loading="showSkeleton" />

        <ui-card-panel heading="Verlauf" :subheading="rangeLabel">
            <LineChart
                :points="chartPoints"
                :secondary="chartVisits"
                :unit="chartUnit"
                :loading="loading"
                label="Seitenaufrufe"
                secondary-label="Besuche"
            />
        </ui-card-panel>

        <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));">
            <MetricList title="Top-Seiten" :items="payload?.topPages || []" :loading="loading" />
            <MetricList title="Referrer" :items="payload?.referrers || []" :loading="loading" />
            <MetricList title="Geräte" :items="payload?.devices || []" format="device" :loading="loading" />
            <MetricList title="Länder" :items="payload?.countries || []" format="country" :loading="loading" />
        </div>
    </div>
</template>
