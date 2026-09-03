<?php

use App\Modules\Library\Infrastructure\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

Route::prefix('library')->middleware(['jwt.auth', 'user.owner'])->group(function (): void {
    Route::get('/users/{userId}/entries', [LibraryController::class, 'indexEntries']);
    Route::post('/users/{userId}/entries', [LibraryController::class, 'storeEntry']);
    Route::get('/users/{userId}/phrases', [LibraryController::class, 'indexPhrases']);
    Route::post('/users/{userId}/phrases', [LibraryController::class, 'storePhrase']);
});
