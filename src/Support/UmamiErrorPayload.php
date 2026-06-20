<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

class UmamiErrorPayload
{
    /**
     * @return array<string, mixed>
     */
    public function make(Throwable $exception): array
    {
        $payload = [
            'configured' => true,
            'message' => 'Umami-Daten konnten nicht geladen werden.',
        ];

        if (! app()->isLocal()) {
            return $payload;
        }

        $payload['error'] = [
            'type' => class_basename($exception),
        ];

        if ($exception instanceof RequestException) {
            $payload['error']['status'] = $exception->response->status();
            $payload['error']['reason'] = $exception->response->reason();
        }

        if ($exception instanceof ConnectionException) {
            $payload['error']['detail'] = str($exception->getMessage())->limit(300)->toString();
        }

        return $payload;
    }
}
