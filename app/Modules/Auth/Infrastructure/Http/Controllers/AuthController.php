<?php

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Infrastructure\Http\Requests\RequestEmailCodeRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\VerifyAccessTokenRequest;
use App\Modules\Auth\Infrastructure\Http\Requests\VerifyEmailCodeRequest;
use App\Modules\Auth\Infrastructure\Http\Takes\GetAuthInitTake;
use App\Modules\Auth\Infrastructure\Http\Takes\GetCountryAuthPolicyTake;
use App\Modules\Auth\Infrastructure\Http\Takes\RequestEmailCodeTake;
use App\Modules\Auth\Infrastructure\Http\Takes\VerifyAccessTokenTake;
use App\Modules\Auth\Infrastructure\Http\Takes\VerifyEmailCodeTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Random\RandomException;

final class AuthController extends Controller
{
    public function __construct(
        private readonly RequestEmailCodeTake $requestEmailCodeTake,
        private readonly VerifyEmailCodeTake $verifyEmailCodeTake,
        private readonly GetAuthInitTake $authInitTake,
        private readonly GetCountryAuthPolicyTake $countryAuthPolicyTake,
        private readonly VerifyAccessTokenTake $verifyAccessTokenTake,
    ) {}

    public function init(Request $request): JsonResponse
    {
        return $this->authInitTake->handle($request);
    }

    /**
     * @throws RandomException
     */
    public function requestEmailCode(RequestEmailCodeRequest $request): JsonResponse
    {
        return $this->requestEmailCodeTake->handle($request);
    }

    public function verifyEmailCode(VerifyEmailCodeRequest $request): JsonResponse
    {
        return $this->verifyEmailCodeTake->handle($request);
    }

    public function countryPolicy(string $countryIso): JsonResponse
    {
        return $this->countryAuthPolicyTake->handle($countryIso);
    }

    public function verifyAccessToken(VerifyAccessTokenRequest $request): JsonResponse
    {
        return $this->verifyAccessTokenTake->handle($request);
    }
}
