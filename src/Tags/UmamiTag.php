<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Tags;

use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Statamic\Tags\Tags;

class UmamiTag extends Tags
{
    protected static $handle = 'umami';

    public function index(): string
    {
        return $this->script();
    }

    public function script(): string
    {
        return app(UmamiClient::class)->scriptTag();
    }
}
