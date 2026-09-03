<?php

use App\Modules\User\Infrastructure\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')->group(function (): void {
    Route::get('/{userId}/profile', [UserProfileController::class, 'show']);
    Route::put('/{userId}/profile', [UserProfileController::class, 'upsert']);
});
