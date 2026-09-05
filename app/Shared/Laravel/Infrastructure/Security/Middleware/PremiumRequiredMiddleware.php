<?php

namespace App\Shared\Laravel\Infrastructure\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as ResponseHttpCode;

/**
 * Premium-only endpoints (stub until real payments land).
 * Grant via: php artisan user:grant-premium user@example.com
 */
final readonly class PremiumRequiredMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = $request->route('userId');

        $isPremium = is_string($userId) && $userId !== ''
            && (bool) DB::table('users')->where('id', $userId)->value('is_premium');

        if (! $isPremium) {
            return response()->json([
                'success' => false,
                'error' => 'premium_required',
                'message' => __('api.subscription.required'),
            ], ResponseHttpCode::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
