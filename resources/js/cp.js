import AnalyticsWidget from './components/AnalyticsWidget.vue';
import Dashboard from './components/Dashboard.vue';
import EntryAnalytics from './components/EntryAnalytics.vue';

Statamic.booting(() => {
    Statamic.$components.register('umami-analytics-dashboard', Dashboard);
    Statamic.$components.register('umami-analytics-widget', AnalyticsWidget);
    Statamic.$components.register('umami_analytics-fieldtype', EntryAnalytics);
});
