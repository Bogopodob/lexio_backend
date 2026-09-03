<?php

namespace App\Modules\Auth\Application\UseCases\Auth\RegisterByEmailPassword;

use App\Modules\Auth\Application\DTO\AuthResult;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\TokenIssuerInterface;

final readonly class RegisterByEmailPasswordUseCase
{
    public function __construct(
        private TokenIssuerInterface $tokenIssuer,
        private AuthUserRepositoryInterface $users,
    ) {}

    public function handle(RegisterByEmailPasswordCommand $command): AuthResult
    {
        $fallbackName = trim($command->email->getLocalPart());
        $name = trim((string) ($command->name ?? $fallbackName));

        $user = $this->users->createWithEmail(
            name: $name !== '' ? $name : 'User',
            email: $command->email,
            password: $command->password,
        );

        $token = $this->tokenIssuer->issue($user);

        return new AuthResult(
            token: $token['token'],
            expiresAt: $token['expires_at'],
            expiresIn: $token['expires_in'],
            user: $user,
        );
    }
}
