<?php

use App\Http\Controllers\Api\Onboarding\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/', [OnboardingController::class, 'show'])->name('show');
    Route::post('/', [OnboardingController::class, 'store'])->name('store');
});
