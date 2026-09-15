<?php

use App\Http\Controllers\Api\SampleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('samples')->group(function () {
        Route::get('getSampleTypes', [SampleController::class, 'getSampleTypes'])
            ->name('samples.getSampleTypes');

        Route::get('getTestCategories', [SampleController::class, 'getTestCategories'])
            ->name('samples.getTestCategories');

        Route::get('getTestMethods', [SampleController::class, 'getTestMethods'])
            ->name('samples.getTestMethods');
    });
});
