<?php

namespace Klickmanufaktur\StatamicUmamiAnalytics\Http\Controllers\Cp;

use Illuminate\Http\JsonResponse;
use Klickmanufaktur\StatamicUmamiAnalytics\Http\Requests\PageAnalyticsRequest;
use Klickmanufaktur\StatamicUmamiAnalytics\Services\AnalyticsData;
use Klickmanufaktur\StatamicUmamiAnalytics\Services\UmamiClient;
use Klickmanufaktur\StatamicUmamiAnalytics\Support\UmamiErrorPayload;
use Statamic\Http\Controllers\CP\CpController;
use Throwable;

class PageController extends CpController
{
    public function __invoke(PageAnalyticsRequest $request, AnalyticsData $analytics, UmamiClient $client): JsonResponse
    {
        if (! $client->isConfigured()) {
            return response()->json([
                'configured' => false,
                'missing' => $client->missingConfiguration(),
            ], 422);
        }

        try {
            return response()->json([
                'configured' => true,
                ...$analytics->page($request->path(), $request->days()),
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function errorResponse(Throwable $exception): JsonResponse
    {
        report($exception);

        return response()->json(app(UmamiErrorPayload::class)->make($exception), 502);
    }
}
