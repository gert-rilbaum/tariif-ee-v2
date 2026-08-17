<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\PriceApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('prices', PriceApiController::class)->name('api.prices');
    Route::get('health', HealthController::class)->name('api.health');
});
