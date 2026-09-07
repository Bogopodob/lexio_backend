<?php

use App\Modules\Auth\Application\Exceptions\InvalidOtpCodeException;
use App\Modules\Auth\Application\Exceptions\TooManyOtpAttemptsException;
use App\Shared\Laravel\Infrastructure\Security\Middleware\EnsureRouteUserMatchesToken;
use App\Shared\Laravel\Infrastructure\Security\Middleware\JwtAuthenticateMiddleware;
use App\Shared\Laravel\Infrastructure\Security\Middleware\PremiumRequiredMiddleware;
use App\Shared\Laravel\Infrastructure\Security\Middleware\SetLocaleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'jwt.auth' => JwtAuthenticateMiddleware::class,
            'user.owner' => EnsureRouteUserMatchesToken::class,
            'premium' => PremiumRequiredMiddleware::class,
        ]);
        $middleware->appendToGroup('api', SetLocaleMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $otpError = static fn (Request $request, string $error, string $message) => $request->is('api/*')
            ? response()->json(['success' => false, 'error' => $error, 'message' => $message], 422)
            : null;

        $exceptions->render(function (TooManyOtpAttemptsException $e, Request $request) use ($otpError) {
            return $otpError($request, 'code_burned', __('api.auth.code_burned'));
        });
        $exceptions->render(function (InvalidOtpCodeException $e, Request $request) use ($otpError) {
            return $otpError($request, 'code_invalid', __('api.auth.code_invalid'));
        });
    })->create();
