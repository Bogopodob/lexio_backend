<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp\VerifyEmailCodeCommand;
use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp\VerifyEmailCodeUseCase;
use App\Modules\Auth\Infrastructure\Http\Requests\VerifyEmailCodeRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\AuthResultResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class VerifyEmailCodeTake
{
    public function __construct(
        private VerifyEmailCodeUseCase $useCase,
    ) {}

    public function handle(VerifyEmailCodeRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new VerifyEmailCodeCommand(
                email: (string) $request->string('email'),
                code: (string) $request->string('code'),
            )
        );

        return AuthResultResponseResource::make($result)->response();
    }
}
