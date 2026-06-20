<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics;

use Illuminate\Support\Facades\Event;
use Klickmanufaktur\StatamicUmamiAnalytics\Fieldtypes\EntryAnalytics;
use Klickmanufaktur\StatamicUmamiAnalytics\Services\AnalyticsData;
use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Klickmanufaktur\StatamicUmamiAnalytics\Widgets\Overview;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\CP\Nav;
use Statamic\Fields\Blueprint;
use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $fieldtypes = [
        EntryAnalytics::class,
    ];

    protected $widgets = [
        Overview::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
        ],
        'publicDirectory' => 'resources/dist',
        'buildDirectory' => 'cp',
    ];

    public function register(): void
    {
        parent::register();

        $this->app->singleton(UmamiClient::class);
        $this->app->singleton(AnalyticsData::class);
    }

    public function bootAddon(): void
    {
        $this->configureEntryAnalyticsTab();
        $this->configureCpNavigation();
    }

    private function configureEntryAnalyticsTab(): void
    {
        if (! config('statamic-umami-analytics.entry_tab.enabled', true)) {
            return;
        }

        $collections = collect(config('statamic-umami-analytics.entry_tab.collections', ['pages']))
            ->map(fn (mixed $collection): string => (string) $collection)
            ->filter()
            ->values();

        if ($collections->isEmpty()) {
            return;
        }

        $tab = (string) config('statamic-umami-analytics.entry_tab.tab', 'analytics');
        $display = (string) config('statamic-umami-analytics.entry_tab.display', 'Zugriffe');
        $field = (string) config('statamic-umami-analytics.entry_tab.field', 'umami_analytics');

        Event::listen(EntryBlueprintFound::class, function (EntryBlueprintFound $event) use ($collections, $tab, $display, $field): void {
            if (! $event->blueprint instanceof Blueprint) {
                return;
            }

            if (! $this->matchesConfiguredCollection($event, $collections->all())) {
                return;
            }

            $this->ensureTab($event->blueprint, $tab, $display);

            $event->blueprint->ensureFieldInTab(
                $field,
                [
                    'type' => 'umami_analytics',
                    'display' => $display,
                    'instructions' => 'Zugriffszahlen für diese Seite.',
                    'instructions_position' => 'above',
                    'listable' => 'hidden',
                    'visibility' => 'read_only',
                ],
                $tab
            );
        });
    }

    /**
     * @param  list<string>  $collections
     */
    private function matchesConfiguredCollection(EntryBlueprintFound $event, array $collections): bool
    {
        foreach ($collections as $collection) {
            if ($event->blueprint->namespace() === "collections/{$collection}") {
                return true;
            }

            if ($event->blueprint->namespace() === "collections.{$collection}") {
                return true;
            }
        }

        if ($event->entry && method_exists($event->entry, 'collection')) {
            return in_array($event->entry->collection()?->handle(), $collections, true);
        }

        $parent = $event->blueprint->parent();

        if ($parent && method_exists($parent, 'handle')) {
            return in_array($parent->handle(), $collections, true);
        }

        return false;
    }

    private function ensureTab(Blueprint $blueprint, string $tab, string $display): void
    {
        if ($blueprint->hasTab($tab)) {
            return;
        }

        $contents = $blueprint->contents();
        $contents['tabs'][$tab] = [
            'display' => $display,
            'sections' => [
                [
                    'fields' => [],
                ],
            ],
        ];

        $blueprint->setContents($contents);
    }

    private function configureCpNavigation(): void
    {
        Nav::extend(function ($nav): void {
            $section = (string) config('statamic-umami-analytics.cp_nav.section', 'Tools');
            $title = (string) config('statamic-umami-analytics.cp_nav.title', 'Analytics');

            $nav->create($title)
                ->section($section)
                ->url(cp_route('umami-analytics.index'))
                ->icon('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m7 14 3-3 3 2 5-7"/><path d="M18 6h-4"/><path d="M18 6v4"/></svg>');
        });
    }
}
