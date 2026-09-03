<?php

namespace App\Shared\Laravel\Infrastructure\Security\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseHttpStatus;

final readonly class InternalApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $expected = (string) config('services.internal_api.token');

        if (! $expected) {
            return response()->json([
                'success' => false,
                'message' => 'internal_api.not_configured',
            ], ResponseHttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        }

        $provided = (string) $request->header('X-Internal-Token');

        if (! $provided || ! hash_equals($expected, $provided)) {
            return response()->json([
                'success' => false,
                'message' => 'internal_api.unauthorized',
            ], ResponseHttpStatus::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
