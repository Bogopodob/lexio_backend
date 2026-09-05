<?php

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Domain\Ports\AccessTokenVerifierInterface;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginByEmailPasswordRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\RegisterByEmailPasswordRequest;
use App\Modules\Auth\Infrastructure\Http\Takes\LoginByEmailPasswordTake;
use App\Modules\Auth\Infrastructure\Http\Takes\RegisterByEmailPasswordTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PasswordAuthController extends Controller
{
    public function __construct(
        private readonly RegisterByEmailPasswordTake $registerByEmailPasswordTake,
        private readonly LoginByEmailPasswordTake $loginByEmailPasswordTake,
        private readonly AccessTokenVerifierInterface $accessTokenVerifier,
        private readonly AuthUserRepositoryInterface $users,
    ) {}

    public function register(RegisterByEmailPasswordRequest $request): JsonResponse
    {
        return $this->registerByEmailPasswordTake->handle($request);
    }

    public function login(LoginByEmailPasswordRequest $request): JsonResponse
    {
        return $this->loginByEmailPasswordTake->handle($request);
    }

    public function me(Request $request): JsonResponse
    {
        $token = (string) $request->bearerToken();
        if ($token === '') {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => __('api.auth.no_token'),
            ], 401);
        }

        $verifiedToken = $this->accessTokenVerifier->verify($token);
        if ($verifiedToken === null) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => __('api.auth.invalid_token'),
            ], 401);
        }

        $user = $this->users->findById($verifiedToken->subject);
        if ($user === null) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => __('api.auth.invalid_subject'),
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => (string) $user->email,
            ],
        ]);
    }
}
