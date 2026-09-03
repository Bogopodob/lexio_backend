<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\RegisterByEmailPassword\RegisterByEmailPasswordCommand;
use App\Modules\Auth\Application\UseCases\Auth\RegisterByEmailPassword\RegisterByEmailPasswordUseCase;
use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use App\Modules\Auth\Infrastructure\Http\Requests\RegisterByEmailPasswordRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\AuthResultResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class RegisterByEmailPasswordTake
{
    public function __construct(private RegisterByEmailPasswordUseCase $useCase) {}

    public function handle(RegisterByEmailPasswordRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(new RegisterByEmailPasswordCommand(
            email: EmailProviderIdValueObject::fromString((string) $request->string('email')),
            password: PasswordValueObject::fromPlainText((string) $request->string('password')),
            name: $request->has('name') ? (string) $request->string('name') : null,
        ));

        return AuthResultResponseResource::make($result)
            ->response()
            ->setStatusCode(201);
    }
}
