<?php

use App\Modules\Auth\Infrastructure\Http\Controllers\AuthController;
use App\Modules\Auth\Infrastructure\Http\Controllers\PasswordAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [PasswordAuthController::class, 'register'])->middleware('throttle:auth-password');
Route::post('/login', [PasswordAuthController::class, 'login'])->middleware('throttle:auth-password');

// OTP-based authentication (Email code)
Route::post('/email/request-code', [AuthController::class, 'requestEmailCode'])->middleware('throttle:otp-request');
Route::post('/email/verify-code', [AuthController::class, 'verifyEmailCode'])->middleware('throttle:otp-verify');

Route::prefix('auth')->group(function (): void {
    // Password-based authentication
    Route::get('/me', [PasswordAuthController::class, 'me']);

    // Token verification
    Route::post('/verify-token', [AuthController::class, 'verifyAccessToken']);

    // Logout (revokes the token immediately)
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
});
