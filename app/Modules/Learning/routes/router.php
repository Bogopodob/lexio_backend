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
    Route::get('/users/{userId}/profiles/{profileId}/goals', [LearningController::class, 'goals']);
    Route::post('/users/{userId}/profiles/{profileId}/goals', [LearningController::class, 'storeGoal']);
    Route::patch('/users/{userId}/profiles/{profileId}/goals/{goalId}', [LearningController::class, 'updateGoal']);
    Route::delete('/users/{userId}/profiles/{profileId}/goals/{goalId}', [LearningController::class, 'destroyGoal']);
    Route::get('/users/{userId}/profiles/{profileId}/achievements', [LearningController::class, 'achievements']);
    Route::post('/users/{userId}/profiles/{profileId}/achievements/evaluate', [LearningController::class, 'evaluateAchievements']);
    Route::get('/users/{userId}/profiles/{profileId}/categories', [LearningController::class, 'categories']);
    Route::post('/users/{userId}/profiles/{profileId}/sessions', [LearningController::class, 'startSession']);
    Route::get('/users/{userId}/profiles/{profileId}/sessions', [LearningController::class, 'sessions']);
    Route::get('/users/{userId}/sessions/{sessionId}/next', [LearningController::class, 'nextCard']);
    Route::post('/users/{userId}/sessions/{sessionId}/answer', [LearningController::class, 'answerCard']);
    Route::post('/users/{userId}/sessions/{sessionId}/finish', [LearningController::class, 'finishSession']);
    Route::get('/users/{userId}/profiles/{profileId}/availability', [LearningController::class, 'availability']);
    Route::post('/users/{userId}/profiles/{profileId}/word-hint', [LearningController::class, 'saveWordHint']);
    Route::get('/users/{userId}/profiles/{profileId}/distractors', [LearningController::class, 'distractors']);
});
