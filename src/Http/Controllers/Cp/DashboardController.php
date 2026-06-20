<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Http\Controllers\Cp;

use Illuminate\View\View;
use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Statamic\Http\Controllers\CP\CpController;

class DashboardController extends CpController
{
    public function __invoke(UmamiClient $client): View
    {
        return view('statamic-umami-analytics::cp.dashboard', [
            'title' => (string) config('statamic-umami-analytics.cp_nav.title', 'Analytics'),
            'overviewUrl' => cp_route('umami-analytics.api.overview'),
            'umamiUrl' => $client->websiteUrl(),
            'periods' => config('statamic-umami-analytics.periods', [7, 30, 90]),
            'defaultPeriod' => (int) config('statamic-umami-analytics.default_period', 30),
            'configured' => $client->isConfigured(),
            'missing' => $client->missingConfiguration(),
        ]);
    }
}
