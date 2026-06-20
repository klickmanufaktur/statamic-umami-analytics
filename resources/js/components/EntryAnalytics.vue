<script setup>
import { Fieldtype } from '@statamic/cms';
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue';
import LineChart from './LineChart.vue';
import MetricList from './MetricList.vue';
import StatGrid from './StatGrid.vue';
import {
    formatDuration,
    formatNumber,
    formatPercent,
    normalizedPageviews,
    normalizedSessions,
    requestErrorMessage,
    statValue,
} from '../support/analytics';

const emit = defineEmits(Fieldtype.emits);
const props = defineProps(Fieldtype.props);
const { expose } = Fieldtype.use(emit, props);
defineExpose(expose);

const days = ref(Number(props.meta?.defaultPeriod || 30));
const loading = ref(false);
const error = ref(null);
const payload = ref(null);
const http = getCurrentInstance()?.appContext.config.globalProperties.$axios;

const configured = computed(() => props.meta?.configured === true);
const path = computed(() => props.meta?.path || '');
const umamiUrl = computed(() => props.meta?.umamiUrl || '');
const periods = computed(() => props.meta?.periods || [7, 30, 90]);

function openUmami() {
    if (umamiUrl.value) {
        window.open(umamiUrl.value, '_blank', 'noopener,noreferrer');
    }
}
const selectedPeriod = computed({
    get: () => String(days.value),
    set: (value) => {
        if (value) {
            days.value = Number(value);
        }
    },
});
const stats = computed(() => payload.value?.stats || {});
const visits = computed(() => statValue(stats.value, 'visits'));
const bounces = computed(() => statValue(stats.value, 'bounces'));
const totalTime = computed(() => statValue(stats.value, 'totaltime'));
const bounceRate = computed(() => visits.value > 0 ? bounces.value / visits.value : 0);
const averageTime = computed(() => visits.value > 0 ? totalTime.value / visits.value : 0);
const chartPoints = computed(() => normalizedPageviews(payload.value?.pageviews));
const chartVisits = computed(() => normalizedSessions(payload.value?.pageviews));
const chartUnit = computed(() => payload.value?.range?.unit || 'day');
const showSkeleton = computed(() => loading.value && !payload.value);

const cards = computed(() => [
    { key: 'pageviews', label: 'Seitenaufrufe', value: formatNumber(statValue(stats.value, 'pageviews')) },
    { key: 'visitors', label: 'Besucher', value: formatNumber(statValue(stats.value, 'visitors')) },
    { key: 'visits', label: 'Besuche', value: formatNumber(visits.value) },
    { key: 'bounce', label: 'Absprungrate', value: formatPercent(bounceRate.value) },
    { key: 'duration', label: 'Ø Besuchsdauer', value: formatDuration(averageTime.value) },
]);

watch(days, () => {
    load();
});

onMounted(() => {
    if (configured.value && path.value) {
        load();
    }
});

async function load() {
    if (!configured.value || !path.value) {
        return;
    }

    loading.value = true;
    error.value = null;
    // Reset so a period switch shows the same skeleton as the initial load.
    payload.value = null;

    try {
        if (!http?.get) {
            throw new Error('Statamic HTTP client is not available.');
        }

        const response = await http.get(props.meta.apiUrl, {
            params: {
                path: path.value,
                days: days.value,
            },
        });

        payload.value = response.data;
    } catch (requestError) {
        error.value = requestErrorMessage(requestError);
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="space-y-4">
        <ui-alert
            v-if="!configured"
            variant="warning"
            heading="Umami ist nicht konfiguriert"
            :text="`Fehlende Werte: ${(meta?.missing || []).join(', ')}`"
        />

        <ui-alert
            v-else-if="!path"
            heading="Keine auswertbare URL"
            text="Für diesen Eintrag gibt es keine auswertbare URL."
        />

        <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <ui-text variant="code" class="min-w-0 max-w-full truncate" :title="path">{{ path }}</ui-text>

                <ui-toggle-group v-model="selectedPeriod" required size="sm">
                    <ui-toggle-item
                        v-for="period in periods"
                        :key="period"
                        :value="String(period)"
                        :label="`${period} Tage`"
                    />
                </ui-toggle-group>
            </div>

            <ui-alert v-if="error" variant="error" :text="error" />

            <StatGrid :stats="cards" :loading="showSkeleton" />

            <ui-card-panel heading="Verlauf">
                <LineChart
                    :points="chartPoints"
                    :secondary="chartVisits"
                    :unit="chartUnit"
                    :loading="loading"
                    label="Seitenaufrufe"
                    secondary-label="Besuche"
                />
            </ui-card-panel>

            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
                <MetricList title="Referrer" :items="payload?.referrers || []" :loading="loading" />
                <MetricList title="Geräte" :items="payload?.devices || []" format="device" :loading="loading" />
                <MetricList title="Länder" :items="payload?.countries || []" format="country" :loading="loading" />
            </div>

            <div v-if="umamiUrl" class="flex justify-center pt-2">
                <ui-button size="sm" variant="ghost" icon="external-link" text="Vollständige Statistik in Umami öffnen" @click="openUmami" />
            </div>
        </template>
    </div>
</template>
