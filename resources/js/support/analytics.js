export function numberValue(value) {
    if (value && typeof value === 'object') {
        return Number(value.value ?? value.y ?? value.views ?? value.pageviews ?? 0);
    }

    return Number(value ?? 0);
}

export function statValue(stats, key) {
    return numberValue(stats?.[key]);
}

export function statChange(stats, key) {
    const value = stats?.[key];

    if (!value || typeof value !== 'object' || value.change === undefined || value.change === null) {
        return null;
    }

    return Number(value.change);
}

export function activeVisitors(active) {
    if (active && typeof active === 'object') {
        return numberValue(active.visitors ?? active.x ?? active.value);
    }

    return numberValue(active);
}

function extractSeries(data, key) {
    const source = Array.isArray(data?.[key])
        ? data[key]
        : Array.isArray(data?.[key]?.[key])
            ? data[key][key]
            : [];

    return source.map((item) => ({
        label: item.x ?? item.t ?? item.date ?? '',
        value: numberValue(item.y ?? item.value ?? item.pageviews ?? item.visitors),
    }));
}

export function normalizedPageviews(data) {
    return extractSeries(data, 'pageviews');
}

export function normalizedSessions(data) {
    return extractSeries(data, 'sessions');
}

function parseChartDate(label) {
    if (!label) {
        return null;
    }

    const date = new Date(String(label).replace(' ', 'T'));

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatChartDate(label, unit = 'day') {
    const date = parseChartDate(label);

    if (!date) {
        return String(label ?? '');
    }

    if (unit === 'hour') {
        return new Intl.DateTimeFormat('de-DE', { hour: '2-digit', minute: '2-digit' }).format(date);
    }

    if (unit === 'month') {
        return new Intl.DateTimeFormat('de-DE', { month: 'short', year: '2-digit' }).format(date);
    }

    return new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: '2-digit' }).format(date);
}

export function formatChartDateLong(label, unit = 'day') {
    const date = parseChartDate(label);

    if (!date) {
        return String(label ?? '');
    }

    if (unit === 'hour') {
        return new Intl.DateTimeFormat('de-DE', {
            weekday: 'short', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit',
        }).format(date);
    }

    if (unit === 'month') {
        return new Intl.DateTimeFormat('de-DE', { month: 'long', year: 'numeric' }).format(date);
    }

    return new Intl.DateTimeFormat('de-DE', { weekday: 'short', day: '2-digit', month: 'long' }).format(date);
}

export function metricLabel(item) {
    return item.x ?? item.label ?? item.path ?? item.url ?? item.name ?? 'Unbekannt';
}

export function metricCount(item) {
    return numberValue(item.y ?? item.views ?? item.pageviews ?? item.visitors ?? item.value);
}

const regionNames = (() => {
    try {
        return new Intl.DisplayNames(['de'], { type: 'region' });
    } catch {
        return null;
    }
})();

export function formatCountry(code) {
    const normalized = String(code ?? '').trim().toUpperCase();

    if (! normalized) {
        return 'Unbekannt';
    }

    try {
        return regionNames?.of(normalized) || normalized;
    } catch {
        return normalized;
    }
}

const deviceLabels = {
    desktop: 'Desktop',
    laptop: 'Laptop',
    mobile: 'Mobil',
    tablet: 'Tablet',
    wearable: 'Wearable',
    console: 'Konsole',
    tv: 'TV',
    smarttv: 'Smart-TV',
};

export function formatDevice(value) {
    const normalized = String(value ?? '').trim();

    if (! normalized) {
        return 'Unbekannt';
    }

    const key = normalized.toLowerCase();

    return deviceLabels[key] || (normalized.charAt(0).toUpperCase() + normalized.slice(1));
}

export function formatDateRange(range) {
    if (! range?.start || ! range?.end) {
        return '';
    }

    const start = parseChartDate(range.start);
    const end = parseChartDate(range.end);

    if (! start || ! end) {
        return `${range.start} – ${range.end}`;
    }

    const formatter = new Intl.DateTimeFormat('de-DE', { day: '2-digit', month: 'long', year: 'numeric' });

    return `${formatter.format(start)} – ${formatter.format(end)}`;
}

export function formatNumber(value) {
    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
}

export function formatPercent(value) {
    if (!Number.isFinite(value)) {
        return '0 %';
    }

    return new Intl.NumberFormat(undefined, {
        style: 'percent',
        maximumFractionDigits: 1,
    }).format(value);
}

export function formatDuration(seconds) {
    const value = Math.max(0, Number(seconds || 0));

    if (value < 60) {
        return `${Math.round(value)} s`;
    }

    const minutes = Math.floor(value / 60);
    const remainingSeconds = Math.round(value % 60);

    if (minutes < 60) {
        return `${minutes} min ${String(remainingSeconds).padStart(2, '0')} s`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${hours} h ${String(remainingMinutes).padStart(2, '0')} min`;
}

export function changeClass(change) {
    if (change === null || change === undefined || Number(change) === 0) {
        return 'text-gray-500 dark:text-gray-400';
    }

    return Number(change) > 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
}

export function changeLabel(change) {
    if (change === null || change === undefined || Number(change) === 0) {
        return '0 %';
    }

    const prefix = Number(change) > 0 ? '+' : '';

    return `${prefix}${formatPercent(Number(change) / 100)}`;
}

export function changeColor(change, invert = false) {
    if (change === null || change === undefined || Number(change) === 0) {
        return 'default';
    }

    const positive = invert ? Number(change) < 0 : Number(change) > 0;

    return positive ? 'green' : 'red';
}

export function requestErrorMessage(error) {
    const response = error.response;
    const data = response?.data || {};
    const details = [];
    const message = data.message || error.message || 'Umami-Daten konnten nicht geladen werden.';
    const responseUrl = response?.request?.responseURL;

    if (response?.status) {
        details.push(`HTTP ${response.status}`);
    }

    if (data.error?.type) {
        details.push(data.error.type);
    }

    if (responseUrl) {
        details.push(responseUrl);
    }

    if (!details.length) {
        return message;
    }

    return `${message} (${details.join(' · ')})`;
}
