<?php

use App\Modules\Learning\Infrastructure\Http\Controllers\LearningController;
use Illuminate\Support\Facades\Route;

Route::prefix('learning')->middleware(['jwt.auth', 'user.owner'])->group(function (): void {
    Route::get('/users/{userId}/profiles', [LearningController::class, 'index']);
    Route::post('/users/{userId}/profiles', [LearningController::class, 'start']);
    Route::patch('/users/{userId}/profiles/{profileId}', [LearningController::class, 'update']);
    Route::post('/users/{userId}/profiles/{profileId}/reviews', [LearningController::class, 'review']);
    Route::get('/users/{userId}/profiles/{profileId}/due', [LearningController::class, 'due']);
    Route::get('/users/{userId}/profiles/{profileId}/stats', [LearningController::class, 'stats']);
});
