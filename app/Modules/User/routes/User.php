<?php

use App\Modules\User\Infrastructure\Http\Controllers\FriendController;
use App\Modules\User\Infrastructure\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function (): void {
    Route::get('/{userId}/avatar', [UserProfileController::class, 'avatar']);
});

Route::prefix('users')->middleware(['jwt.auth', 'user.owner'])->group(function (): void {
    Route::get('/{userId}/profile', [UserProfileController::class, 'show']);
    Route::put('/{userId}/profile', [UserProfileController::class, 'upsert']);
    Route::post('/{userId}/avatar', [UserProfileController::class, 'storeAvatar']);

    Route::get('/{userId}/friends', [FriendController::class, 'index']);
    Route::get('/{userId}/friends/leaderboard', [FriendController::class, 'leaderboard'])->middleware('premium');
    Route::post('/{userId}/friends/requests', [FriendController::class, 'store'])->middleware('premium');
    Route::get('/{userId}/friends/requests', [FriendController::class, 'requests']);
    Route::post('/{userId}/friends/requests/{requestId}/accept', [FriendController::class, 'accept']);
    Route::post('/{userId}/friends/requests/{requestId}/decline', [FriendController::class, 'decline']);
    Route::delete('/{userId}/friends/{friendshipId}', [FriendController::class, 'destroy']);
    Route::get('/{userId}/friends/search', [FriendController::class, 'search']);
});
