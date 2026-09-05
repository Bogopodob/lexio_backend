<?php

namespace App\Shared\Laravel\Infrastructure\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseHttpCode;

/**
 * Requires a verified JWT identity (set by JwtAuthenticateMiddleware)
 * and ensures the {userId} route parameter belongs to that identity.
 */
final readonly class EnsureRouteUserMatchesToken
{
    public function handle(Request $request, Closure $next)
    {
        $authUserId = $request->attributes->get('auth_user_id');

        if (! $authUserId) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => __('api.auth.unauthenticated'),
            ], ResponseHttpCode::HTTP_UNAUTHORIZED);
        }

        $routeUserId = $request->route('userId');

        if ($routeUserId !== null && (string) $routeUserId !== (string) $authUserId) {
            return response()->json([
                'success' => false,
                'error' => 'forbidden',
                'message' => __('api.auth.forbidden'),
            ], ResponseHttpCode::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
