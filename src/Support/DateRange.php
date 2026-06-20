<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Support;

use Carbon\CarbonImmutable;

class DateRange
{
    public function __construct(
        public readonly int $days,
        public readonly string $timezone,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function forDays(int $days, string $timezone): self
    {
        $end = CarbonImmutable::now($timezone);
        $start = $end->subDays(max(1, $days) - 1)->startOfDay();

        return new self($days, $timezone, $start, $end);
    }

    /**
     * @return array<string, int|string>
     */
    public function query(?string $unit = null): array
    {
        return [
            'startAt' => $this->timestampInMilliseconds($this->start),
            'endAt' => $this->timestampInMilliseconds($this->end),
            'unit' => $unit ?? $this->unit(),
            'timezone' => $this->timezone,
        ];
    }

    public function unit(): string
    {
        if ($this->days <= 2) {
            return 'hour';
        }

        if ($this->days > 185) {
            return 'month';
        }

        return 'day';
    }

    /**
     * Every bucket between start and end at the current unit granularity.
     * Umami only returns buckets that have data, so this is used to zero-fill
     * the series for a continuous chart.
     *
     * @return list<array{x: string, key: string}>
     */
    public function buckets(): array
    {
        [$step, $start, $keyLength] = match ($this->unit()) {
            'hour' => ['addHour', $this->start->startOfHour(), 13],
            'month' => ['addMonth', $this->start->startOfMonth(), 7],
            default => ['addDay', $this->start->startOfDay(), 10],
        };

        $buckets = [];
        $cursor = $start;

        // Hard cap guards against accidental unbounded ranges.
        for ($i = 0; $i < 1000 && $cursor->lessThanOrEqualTo($this->end); $i++) {
            $x = $cursor->format('Y-m-d H:i:s');
            $buckets[] = ['x' => $x, 'key' => substr($x, 0, $keyLength)];
            $cursor = $cursor->{$step}();
        }

        return $buckets;
    }

    /**
     * Reduce an Umami time-series label to the bucket key for the current unit.
     */
    public function bucketKey(string $label): string
    {
        $length = match ($this->unit()) {
            'hour' => 13,
            'month' => 7,
            default => 10,
        };

        return substr($label, 0, $length);
    }

    /**
     * @return array<string, string|int>
     */
    public function toArray(): array
    {
        return [
            'days' => $this->days,
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'timezone' => $this->timezone,
            'unit' => $this->unit(),
        ];
    }

    private function timestampInMilliseconds(CarbonImmutable $date): int
    {
        return ((int) $date->utc()->format('U')) * 1000;
    }
}
