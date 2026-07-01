<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Klickmanufaktur\StatamicUmamiAnalytics\Exceptions\UmamiConfigurationException;
use Klickmanufaktur\StatamicUmamiAnalytics\Support\DateRange;
use Throwable;

class UmamiClient
{
    public function isConfigured(): bool
    {
        return $this->missingConfiguration() === [];
    }

    /**
     * @return list<string>
     */
    public function missingConfiguration(): array
    {
        $missing = [];

        if (! $this->apiUrl()) {
            $missing[] = 'UMAMI_API_URL';
        }

        if (! $this->websiteId()) {
            $missing[] = 'UMAMI_WEBSITE_ID';
        }

        if (! $this->hasAuthentication()) {
            $missing[] = 'UMAMI_API_KEY oder UMAMI_TOKEN oder UMAMI_USERNAME/UMAMI_PASSWORD';
        }

        return $missing;
    }

    /**
     * Public Umami web UI base URL (where editors log in).
     */
    public function dashboardUrl(): string
    {
        $configured = trim((string) config('statamic-umami-analytics.dashboard_url'));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $api = $this->apiUrl();

        if (str_contains($api, 'api.umami.is')) {
            return 'https://cloud.umami.is';
        }

        return (string) preg_replace('#/(api|v\d+)$#', '', $api);
    }

    /**
     * Deep link to the configured website inside the Umami web UI.
     */
    public function websiteUrl(): string
    {
        $base = $this->dashboardUrl();

        if ($base === '') {
            return '';
        }

        $website = $this->websiteId();

        return $website !== '' ? "{$base}/websites/{$website}" : $base;
    }

    /**
     * URL of the Umami tracking script.
     */
    public function scriptUrl(): string
    {
        $configured = trim((string) config('statamic-umami-analytics.script_url'));

        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $base = $this->dashboardUrl();

        return $base === '' ? '' : "{$base}/script.js";
    }

    /**
     * HTML `<script>` tag that loads the Umami tracking script for the configured website.
     */
    public function scriptTag(): string
    {
        $src = $this->scriptUrl();
        $website = $this->websiteId();

        if ($src === '' || $website === '') {
            return '';
        }

        $attributes = [
            'defer' => true,
            'src' => $src,
            'data-website-id' => $website,
        ];

        if ($domains = $this->scriptDomains()) {
            $attributes['data-domains'] = $domains;
        }

        if (config('statamic-umami-analytics.tracking.performance', true)) {
            $attributes['data-performance'] = 'true';
        }

        if (config('statamic-umami-analytics.tracking.do_not_track', true)) {
            $attributes['data-do-not-track'] = 'true';
        }

        return '<script '.$this->renderAttributes($attributes).'></script>';
    }

    /**
     * Comma-delimited domain list the tracker is allowed to run on, e.g. "example.com,www.example.com".
     * Derived from `app.url` unless overridden, and disabled entirely when set to `false`.
     */
    private function scriptDomains(): ?string
    {
        $configured = config('statamic-umami-analytics.tracking.domains');

        if ($configured === false) {
            return null;
        }

        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host) {
            return null;
        }

        $bare = preg_replace('/^www\./', '', $host);

        return $bare === $host ? "{$bare},www.{$bare}" : "www.{$bare},{$bare}";
    }

    /**
     * @param  array<string, bool|string>  $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            $parts[] = $value === true
                ? htmlspecialchars($name, ENT_QUOTES)
                : sprintf('%s="%s"', htmlspecialchars($name, ENT_QUOTES), htmlspecialchars((string) $value, ENT_QUOTES));
        }

        return implode(' ', $parts);
    }

    /**
     * Fetch every dataset the overview needs in a single parallel batch.
     *
     * @return array<string, mixed>
     */
    public function overview(DateRange $range): array
    {
        $website = $this->websiteId();
        $base = $range->query();

        $data = $this->batch([
            'stats' => ['uri' => "websites/{$website}/stats", 'query' => $base],
            'pageviews' => ['uri' => "websites/{$website}/pageviews", 'query' => $base],
            'topPages' => ['uri' => "websites/{$website}/metrics/expanded", 'query' => [...$base, 'type' => 'path', 'limit' => 10]],
            'referrers' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'referrer', 'limit' => 8]],
            'devices' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'device', 'limit' => 8]],
            'countries' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'country', 'limit' => 8]],
            'active' => ['uri' => "websites/{$website}/active", 'query' => [], 'ttl' => $this->activeCacheTtl()],
        ]);

        return [
            'stats' => $data['stats'],
            'active' => $data['active'],
            'pageviews' => $data['pageviews'],
            'topPages' => $this->sortByCount($data['topPages'], 'pageviews'),
            'referrers' => $this->sortByCount($data['referrers'], 'y'),
            'devices' => $this->sortByCount($data['devices'], 'y'),
            'countries' => $this->sortByCount($data['countries'], 'y'),
        ];
    }

    /**
     * Fetch every dataset a single page needs in one parallel batch.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function page(DateRange $range, array $filters): array
    {
        $website = $this->websiteId();
        $base = [...$range->query(), ...$filters];

        $data = $this->batch([
            'stats' => ['uri' => "websites/{$website}/stats", 'query' => $base],
            'pageviews' => ['uri' => "websites/{$website}/pageviews", 'query' => $base],
            'referrers' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'referrer', 'limit' => 8]],
            'devices' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'device', 'limit' => 8]],
            'countries' => ['uri' => "websites/{$website}/metrics", 'query' => [...$base, 'type' => 'country', 'limit' => 8]],
        ]);

        return [
            'stats' => $data['stats'],
            'pageviews' => $data['pageviews'],
            'referrers' => $this->sortByCount($data['referrers'], 'y'),
            'devices' => $this->sortByCount($data['devices'], 'y'),
            'countries' => $this->sortByCount($data['countries'], 'y'),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function stats(DateRange $range, array $filters = []): array
    {
        return $this->get("websites/{$this->websiteId()}/stats", $this->query($range, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function pageviews(DateRange $range, array $filters = []): array
    {
        return $this->get("websites/{$this->websiteId()}/pageviews", $this->query($range, $filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function metrics(DateRange $range, string $type, int $limit = 10, array $filters = [], bool $expanded = false): array
    {
        $uri = $expanded
            ? "websites/{$this->websiteId()}/metrics/expanded"
            : "websites/{$this->websiteId()}/metrics";

        $data = $this->get($uri, $this->query($range, [
            ...$filters,
            'type' => $type,
            'limit' => $limit,
        ]));

        return $this->sortByCount(is_array($data) ? $data : [], $expanded ? 'pageviews' : 'y');
    }

    /**
     * Umami's API doesn't guarantee metric rows come back sorted by count
     * (e.g. `/metrics/expanded` has been observed returning path order), so
     * enforce descending order here rather than trusting the response order.
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function sortByCount(array $rows, string $key): array
    {
        $rows = array_values($rows);

        usort($rows, fn (array $a, array $b): int => (int) ($b[$key] ?? 0) <=> (int) ($a[$key] ?? 0));

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function active(): array
    {
        return $this->get("websites/{$this->websiteId()}/active", [], $this->activeCacheTtl());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function get(string $uri, array $query, ?int $ttl = null): array
    {
        if (! $this->isConfigured()) {
            throw UmamiConfigurationException::missing($this->missingConfiguration());
        }

        $query = $this->cleanQuery($query);

        return Cache::remember($this->cacheKey($uri, $query), now()->addSeconds($ttl ?? $this->cacheTtl()), function () use ($uri, $query): array {
            $response = $this->request()
                ->get($uri, $query)
                ->throw()
                ->json();

            return is_array($response) ? $response : [];
        });
    }

    /**
     * Run several requests concurrently, serving any already-cached entries
     * from the cache and caching the freshly fetched ones.
     *
     * @param  array<string, array{uri: string, query: array<string, mixed>, ttl?: int}>  $requests
     * @return array<string, array<string, mixed>>
     */
    private function batch(array $requests): array
    {
        if (! $this->isConfigured()) {
            throw UmamiConfigurationException::missing($this->missingConfiguration());
        }

        $results = [];
        $pending = [];

        foreach ($requests as $key => $request) {
            $query = $this->cleanQuery($request['query']);
            $cacheKey = $this->cacheKey($request['uri'], $query);
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $results[$key] = $cached;

                continue;
            }

            $pending[$key] = [
                'uri' => $request['uri'],
                'query' => $query,
                'cacheKey' => $cacheKey,
                'ttl' => $request['ttl'] ?? $this->cacheTtl(),
            ];
        }

        if ($pending === []) {
            return $results;
        }

        // Resolve the login token once up front so the pooled requests don't each block on it.
        $token = $this->authMode() === 'login' ? $this->loginToken() : null;

        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (string $key) => $this->poolRequest($pool->as($key), $token)
                ->get($pending[$key]['uri'], $pending[$key]['query']),
            array_keys($pending)
        ));

        foreach ($pending as $key => $request) {
            $response = $responses[$key];

            if ($response instanceof Throwable) {
                throw $response;
            }

            /** @var Response $response */
            $response->throw();
            $data = is_array($response->json()) ? $response->json() : [];

            Cache::put($request['cacheKey'], $data, now()->addSeconds($request['ttl']));
            $results[$key] = $data;
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function query(DateRange $range, array $filters): array
    {
        return [
            ...$range->query(),
            ...$filters,
        ];
    }

    private function request(): PendingRequest
    {
        return $this->withAuth(
            Http::acceptJson()
                ->baseUrl($this->apiUrl())
                ->timeout($this->timeout())
                ->connectTimeout($this->connectTimeout())
                ->retry([100, 500])
        );
    }

    private function poolRequest(PendingRequest $request, ?string $token): PendingRequest
    {
        return $this->withAuth(
            $request
                ->acceptJson()
                ->baseUrl($this->apiUrl())
                ->timeout($this->timeout())
                ->connectTimeout($this->connectTimeout())
                ->retry([100, 500]),
            $token
        );
    }

    private function withAuth(PendingRequest $request, ?string $token = null): PendingRequest
    {
        return match ($this->authMode()) {
            'api_key' => $request->withHeaders([
                $this->apiKeyHeader() => (string) config('statamic-umami-analytics.api_key'),
            ]),
            'token' => $request->withToken((string) config('statamic-umami-analytics.token')),
            'login' => $request->withToken($token ?? $this->loginToken()),
            default => $request,
        };
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function cacheKey(string $uri, array $query): string
    {
        return 'statamic-umami-analytics:'.sha1($this->apiUrl().'|'.$uri.'|'.serialize($query));
    }

    private function loginToken(): string
    {
        $cacheKey = 'statamic-umami-analytics:auth-token:'.sha1($this->apiUrl().'|'.(string) config('statamic-umami-analytics.username'));

        return Cache::remember($cacheKey, now()->addSeconds($this->authTokenTtl()), function (): string {
            $response = Http::acceptJson()
                ->baseUrl($this->apiUrl())
                ->timeout($this->timeout())
                ->connectTimeout($this->connectTimeout())
                ->post('auth/login', [
                    'username' => (string) config('statamic-umami-analytics.username'),
                    'password' => (string) config('statamic-umami-analytics.password'),
                ])
                ->throw()
                ->json();

            $token = Arr::get($response, 'token');

            if (! is_string($token) || $token === '') {
                throw new UmamiConfigurationException('Umami Login hat keinen API-Token geliefert.');
            }

            return $token;
        });
    }

    private function hasAuthentication(): bool
    {
        return match ($this->authMode()) {
            'api_key' => filled(config('statamic-umami-analytics.api_key')),
            'token' => filled(config('statamic-umami-analytics.token')),
            'login' => filled(config('statamic-umami-analytics.username')) && filled(config('statamic-umami-analytics.password')),
            default => false,
        };
    }

    private function authMode(): string
    {
        $configured = (string) config('statamic-umami-analytics.auth', 'auto');

        if ($configured !== 'auto') {
            return $configured;
        }

        if (filled(config('statamic-umami-analytics.api_key'))) {
            return 'api_key';
        }

        if (filled(config('statamic-umami-analytics.token'))) {
            return 'token';
        }

        if (filled(config('statamic-umami-analytics.username')) && filled(config('statamic-umami-analytics.password'))) {
            return 'login';
        }

        return 'none';
    }

    private function apiUrl(): string
    {
        return rtrim((string) config('statamic-umami-analytics.api_url'), '/');
    }

    private function websiteId(): string
    {
        return trim((string) config('statamic-umami-analytics.website_id'));
    }

    private function apiKeyHeader(): string
    {
        return (string) config('statamic-umami-analytics.api_key_header', 'x-umami-api-key');
    }

    private function timeout(): int
    {
        return max(1, (int) config('statamic-umami-analytics.timeout', 10));
    }

    private function connectTimeout(): int
    {
        return max(1, (int) config('statamic-umami-analytics.connect_timeout', 3));
    }

    private function cacheTtl(): int
    {
        return max(0, (int) config('statamic-umami-analytics.cache_ttl', 300));
    }

    private function activeCacheTtl(): int
    {
        return max(0, (int) config('statamic-umami-analytics.active_cache_ttl', 15));
    }

    private function authTokenTtl(): int
    {
        return max(60, (int) config('statamic-umami-analytics.auth_token_ttl', 3300));
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function cleanQuery(array $query): array
    {
        return collect($query)
            ->reject(fn (mixed $value): bool => $value === null || $value === '')
            ->all();
    }
}
