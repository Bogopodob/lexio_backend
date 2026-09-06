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
    Route::post('/users/{userId}/shares', [LibraryController::class, 'grantShare']);
    Route::get('/users/{userId}/shares', [LibraryController::class, 'listShares']);
    Route::delete('/users/{userId}/shares/{shareId}', [LibraryController::class, 'revokeShare']);
    Route::get('/users/{userId}/shared-with-me', [LibraryController::class, 'sharedWithMe']);
    Route::get('/users/{userId}/shared/entries', [LibraryController::class, 'sharedEntries']);
    Route::get('/users/{userId}/shared/entries/{entryId}', [LibraryController::class, 'sharedEntry']);
    Route::get('/users/{userId}/shared/phrases', [LibraryController::class, 'sharedPhrases']);
    Route::get('/users/{userId}/shared/phrases/{phraseId}', [LibraryController::class, 'sharedPhrase']);
});
