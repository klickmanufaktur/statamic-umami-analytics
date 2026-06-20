<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Widgets;

use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Statamic\Widgets\VueComponent;
use Statamic\Widgets\Widget;

class Overview extends Widget
{
    protected static $handle = 'umami_analytics';

    public function component()
    {
        $client = app(UmamiClient::class);
        $periods = config('statamic-umami-analytics.periods', [7, 30, 90]);
        $default = (int) config('statamic-umami-analytics.default_period', 30);

        $days = (int) $this->config('days', $default);

        if (! in_array($days, array_map('intval', $periods), true)) {
            $days = $default;
        }

        return VueComponent::render('umami-analytics-widget', [
            'title' => (string) $this->config('title', 'Analytics'),
            'days' => $days,
            'overviewUrl' => cp_route('umami-analytics.api.overview'),
            'indexUrl' => cp_route('umami-analytics.index'),
            'umamiUrl' => $client->websiteUrl(),
            'configured' => $client->isConfigured(),
            'missing' => $client->missingConfiguration(),
            'showChart' => (bool) $this->config('chart', true),
        ]);
    }
}
