<?php

use Illuminate\Support\Facades\Route;
use Klickmanufaktur\StatamicUmamiAnalytics\Http\Controllers\Cp\DashboardController;
use Klickmanufaktur\StatamicUmamiAnalytics\Http\Controllers\Cp\OverviewController;
use Klickmanufaktur\StatamicUmamiAnalytics\Http\Controllers\Cp\PageController;

Route::get('umami-analytics', DashboardController::class)
    ->name('umami-analytics.index');

Route::get('api/umami-analytics/overview', OverviewController::class)
    ->name('umami-analytics.api.overview');

Route::get('api/umami-analytics/page', PageController::class)
    ->name('umami-analytics.api.page');
