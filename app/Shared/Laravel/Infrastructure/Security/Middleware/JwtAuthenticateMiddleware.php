<?php

namespace App\Shared\Laravel\Infrastructure\Security\Middleware;

use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\RevokedTokenStoreInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\TokenGeneratorInterface;
use Closure;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseHttpCode;

final readonly class JwtAuthenticateMiddleware
{
    public function __construct(
        private TokenGeneratorInterface $tokenGenerator,
        private RevokedTokenStoreInterface $revokedTokens,
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Token not provided',
            ], ResponseHttpCode::HTTP_UNAUTHORIZED);
        }

        $payload = $this->tokenGenerator->verify($token);

        if (! $payload) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Invalid or expired token',
            ], ResponseHttpCode::HTTP_UNAUTHORIZED);
        }

        if ($this->revokedTokens->isRevoked(hash('sha256', $token))) {
            return response()->json([
                'message' => 'Unauthenticated',
                'error' => 'Token revoked',
            ], ResponseHttpCode::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('auth_user_id', $payload->id->toString());
        $request->attributes->set('auth_user_email', $payload->email->value());
        $request->attributes->set('auth_payload', $payload);

        // Опционально: проверка IP (если важна безопасность)
        if ($payload->ipAddress && $payload->ipAddress !== $request->ip()) {
            Log::warning('IP address mismatch in JWT', [
                'token_ip' => $payload->ipAddress,
                'request_ip' => $request->ip(),
            ]);
            // Можно отклонить запрос:
            // return response()->json(['message' => 'IP mismatch'], 403);
        }

        $user = new GenericUser([
            'id' => $payload->id->toString(),
            'email' => $payload->email->value(),
            'abilities' => $payload->abilities,
            'ipAddress' => $payload->ipAddress,
            'userAgent' => $payload->userAgent,
            'issuedAt' => $payload->issuedAt,
        ]);

        Auth::setUser($user);

        return $next($request);
    }
}
