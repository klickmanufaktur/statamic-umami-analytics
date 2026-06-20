# Statamic Umami Analytics

Reusable Statamic addon for showing Umami analytics inside the control panel.

## Features

- CP navigation item with an analytics overview.
- Read-only analytics tab on configured entry collections.
- Server-side Umami API client with timeouts and short cache TTL.
- Supports Umami Cloud API keys, self-hosted bearer tokens, and self-hosted username/password login.

## Installation

Install as a Composer path repository while developing locally:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../_packages/statamic-umami-analytics",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "klickmanufaktur/statamic-umami-analytics": "*"
    }
}
```

Then run:

```bash
composer update klickmanufaktur/statamic-umami-analytics --with-dependencies
php artisan vendor:publish --tag=statamic-umami-analytics --force
```

## Configuration

Publish the config or set environment variables directly.

### Umami Cloud

```dotenv
UMAMI_API_URL=https://api.umami.is/v1
UMAMI_WEBSITE_ID=00000000-0000-0000-0000-000000000000
UMAMI_AUTH=api_key
UMAMI_API_KEY=...
```

### Self-hosted with bearer token

```dotenv
UMAMI_API_URL=https://umami.example.com/api
UMAMI_WEBSITE_ID=00000000-0000-0000-0000-000000000000
UMAMI_AUTH=token
UMAMI_TOKEN=...
```

### Self-hosted with login

```dotenv
UMAMI_API_URL=https://umami.example.com/api
UMAMI_WEBSITE_ID=00000000-0000-0000-0000-000000000000
UMAMI_AUTH=login
UMAMI_USERNAME=...
UMAMI_PASSWORD=...
```

## Dashboard Widget

The addon ships a compact CP dashboard widget (handle `umami_analytics`) showing
the key metrics (page views, visitors, visits, bounce rate) plus an optional
mini chart. Enable it in your app's `config/statamic/cp.php` under `widgets`:

```php
'widgets' => [
    [
        'type' => 'umami_analytics',
        'width' => 'md',   // sm | md | lg | full
        'days' => 30,      // must be one of the configured `periods`
        'chart' => true,   // show the mini chart
        'title' => 'Analytics',
    ],
],
```

The widget links through to the full Umami dashboard and respects the same
configuration (API credentials, periods, dashboard URL) as the rest of the addon.

## Entry Tab

By default, the addon injects a `Zugriffe` tab into the `pages` collection.

```php
'entry_tab' => [
    'enabled' => true,
    'collections' => ['pages', 'news'],
    'tab' => 'analytics',
    'display' => 'Zugriffe',
    'field' => 'umami_analytics',
],
```
