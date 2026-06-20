<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Fieldtypes;

use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Klickmanufaktur\StatamicUmamiAnalytics\Support\EntryPathResolver;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Fields\Fieldtype;

class EntryAnalytics extends Fieldtype
{
    protected static $handle = 'umami_analytics';

    protected $categories = ['special'];

    protected $defaultable = false;

    protected $icon = 'dashboard';

    protected function configFieldItems(): array
    {
        return [];
    }

    public function defaultValue(): mixed
    {
        return null;
    }

    public function preProcess($value): mixed
    {
        return null;
    }

    public function process($value): mixed
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function preload(): array
    {
        $parent = $this->field->parent();
        $client = app(UmamiClient::class);

        if (! $parent instanceof EntryContract) {
            return $this->payload($client, null);
        }

        return $this->payload($client, app(EntryPathResolver::class)->resolve($parent));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(UmamiClient $client, ?string $path): array
    {
        return [
            'configured' => $client->isConfigured(),
            'missing' => $client->missingConfiguration(),
            'path' => $path,
            'display' => (string) config('statamic-umami-analytics.entry_tab.display', 'Analytics'),
            'apiUrl' => cp_route('umami-analytics.api.page'),
            'umamiUrl' => $client->websiteUrl(),
            'periods' => config('statamic-umami-analytics.periods', [7, 30, 90]),
            'defaultPeriod' => (int) config('statamic-umami-analytics.default_period', 30),
        ];
    }
}
