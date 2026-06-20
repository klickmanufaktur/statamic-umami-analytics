<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Support;

use Statamic\Contracts\Entries\Entry as EntryContract;

class EntryPathResolver
{
    public function resolve(EntryContract $entry): ?string
    {
        $url = $entry->absoluteUrl() ?: $entry->url() ?: $entry->uri();

        if (! $url) {
            return null;
        }

        $path = parse_url((string) $url, PHP_URL_PATH);

        // Home page (or any URL without a path component) maps to "/".
        if (! is_string($path) || trim($path, '/') === '') {
            return '/';
        }

        return '/'.trim($path, '/');
    }
}
