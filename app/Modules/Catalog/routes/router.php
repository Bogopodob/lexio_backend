<?php

use App\Modules\Catalog\Infrastructure\Http\Controllers\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalog')->group(function (): void {
    Route::get('/languages', [CatalogController::class, 'languages']);
    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/entries/search', [CatalogController::class, 'search']);
    Route::get('/word-of-day', [CatalogController::class, 'wordOfDay']);
    Route::get('/quiz-round', [CatalogController::class, 'quizRound']);
    Route::get('/entries/{entryId}', [CatalogController::class, 'show']);
});
