<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\VerifyAccessToken\VerifyAccessTokenCommand;
use App\Modules\Auth\Application\UseCases\Auth\VerifyAccessToken\VerifyAccessTokenUseCase;
use App\Modules\Auth\Infrastructure\Http\Requests\VerifyAccessTokenRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\VerifiedAccessTokenResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class VerifyAccessTokenTake
{
    public function __construct(
        private VerifyAccessTokenUseCase $useCase,
    ) {}

    public function handle(VerifyAccessTokenRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new VerifyAccessTokenCommand(
                token: (string) $request->bearerToken(),
            )
        );

        return VerifiedAccessTokenResponseResource::make($result)->response();
    }
}
