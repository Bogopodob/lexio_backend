<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailPassword\LoginByEmailPasswordCommand;
use App\Modules\Auth\Application\UseCases\Auth\LoginByEmailPassword\LoginByEmailPasswordUseCase;
use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginByEmailPasswordRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\AuthResultResponseResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as ResponseHttpStatus;

final readonly class LoginByEmailPasswordTake
{
    public function __construct(private LoginByEmailPasswordUseCase $useCase) {}

    public function handle(LoginByEmailPasswordRequest $request): JsonResponse
    {
        try {
            $result = $this->useCase->handle(new LoginByEmailPasswordCommand(
                email: EmailProviderIdValueObject::fromString($request->string('email')),
                password: PasswordValueObject::fromPlainText($request->string('password')),
            ));
        } catch (InvalidCredentialsException $exception) {
            return response()->json([
                'success' => false,
                'error' => __('auth.failed'),
                'message' => $exception->getMessage(),
            ], ResponseHttpStatus::HTTP_UNAUTHORIZED);
        }

        return AuthResultResponseResource::make($result)->response();
    }
}
