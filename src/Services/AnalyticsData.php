<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Services;

use Klickmanufaktur\StatamicUmamiAnalytics\Support\DateRange;

class AnalyticsData
{
    public function __construct(private readonly UmamiClient $client) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(int $days): array
    {
        $range = $this->range($days);
        $data = $this->client->overview($range);
        $data['pageviews'] = $this->fillSeries($range, $data['pageviews'] ?? []);

        return [
            'range' => $range->toArray(),
            ...$data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function page(string $path, int $days): array
    {
        $range = $this->range($days);
        $data = $this->client->page($range, ['path' => $path]);
        $data['pageviews'] = $this->fillSeries($range, $data['pageviews'] ?? []);

        return [
            'range' => $range->toArray(),
            'path' => $path,
            ...$data,
        ];
    }

    /**
     * Umami only returns buckets that have data. Zero-fill every bucket in the
     * range so the chart renders a continuous baseline instead of a flat block.
     *
     * @param  array<string, mixed>  $pageviews
     * @return array<string, list<array{x: string, y: int|float}>>
     */
    private function fillSeries(DateRange $range, array $pageviews): array
    {
        $buckets = $range->buckets();

        return [
            'pageviews' => $this->mergeSeries($range, $buckets, $pageviews['pageviews'] ?? []),
            'sessions' => $this->mergeSeries($range, $buckets, $pageviews['sessions'] ?? []),
        ];
    }

    /**
     * @param  list<array{x: string, key: string}>  $buckets
     * @param  array<int, array<string, mixed>>  $series
     * @return list<array{x: string, y: int|float}>
     */
    private function mergeSeries(DateRange $range, array $buckets, array $series): array
    {
        $values = [];

        foreach ($series as $point) {
            if (! is_array($point) || ! isset($point['x'])) {
                continue;
            }

            $values[$range->bucketKey((string) $point['x'])] = $point['y'] ?? 0;
        }

        return array_map(fn (array $bucket): array => [
            'x' => $bucket['x'],
            'y' => $values[$bucket['key']] ?? 0,
        ], $buckets);
    }

    private function range(int $days): DateRange
    {
        return DateRange::forDays($days, (string) config('statamic-umami-analytics.timezone', config('app.timezone', 'UTC')));
    }
}
