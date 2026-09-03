<?php

use App\Modules\Auth\Infrastructure\Http\Controllers\AuthController;
use App\Modules\Auth\Infrastructure\Http\Controllers\PasswordAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [PasswordAuthController::class, 'register']);
Route::post('/login', [PasswordAuthController::class, 'login']);

// OTP-based authentication (Email code)
Route::post('/email/request-code', [AuthController::class, 'requestEmailCode']);
Route::post('/email/verify-code', [AuthController::class, 'verifyEmailCode']);

Route::prefix('auth')->group(function (): void {
    // Password-based authentication
    Route::get('/me', [PasswordAuthController::class, 'me']);

    // Token verification
    Route::post('/verify-token', [AuthController::class, 'verifyAccessToken']);
});
