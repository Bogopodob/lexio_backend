<?php

use App\Modules\Library\Infrastructure\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

Route::prefix('library')->middleware(['jwt.auth', 'user.owner'])->group(function (): void {
    Route::get('/users/{userId}/entries', [LibraryController::class, 'indexEntries']);
    Route::post('/users/{userId}/entries', [LibraryController::class, 'storeEntry']);
    Route::get('/users/{userId}/entries/{entryId}', [LibraryController::class, 'showEntry']);
    Route::put('/users/{userId}/entries/{entryId}', [LibraryController::class, 'updateEntry']);
    Route::delete('/users/{userId}/entries/{entryId}', [LibraryController::class, 'destroyEntry']);
    Route::get('/users/{userId}/phrases', [LibraryController::class, 'indexPhrases']);
    Route::post('/users/{userId}/phrases', [LibraryController::class, 'storePhrase']);
    Route::get('/users/{userId}/phrases/{phraseId}', [LibraryController::class, 'showPhrase']);
    Route::put('/users/{userId}/phrases/{phraseId}', [LibraryController::class, 'updatePhrase']);
    Route::delete('/users/{userId}/phrases/{phraseId}', [LibraryController::class, 'destroyPhrase']);
    Route::post('/users/{userId}/media', [LibraryController::class, 'uploadMedia']);
    Route::get('/users/{userId}/media/{mediaId}', [LibraryController::class, 'streamMedia']);
    Route::post('/users/{userId}/speak', [LibraryController::class, 'speak']);
    Route::post('/transcribe', [LibraryController::class, 'transcribe']);
});
