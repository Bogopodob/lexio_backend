<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailPassword;

use App\Modules\Auth\Application\DTO\AuthResult;
use App\Modules\Auth\Application\Exceptions\InvalidCredentialsException;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;
use App\Modules\Auth\Domain\Ports\TokenIssuerInterface;

final readonly class LoginByEmailPasswordUseCase
{
    public function __construct(
        private TokenIssuerInterface $tokenIssuer,
        private AuthUserRepositoryInterface $userRepository,
    ) {}

    public function handle(LoginByEmailPasswordCommand $command): AuthResult
    {
        $user = $this->userRepository->findByEmail($command->email);

        if (
            $user === null
            || $user->password === null
            || ! $user->password->verify($command->password)
        ) {
            throw new InvalidCredentialsException('Invalid email or password');
        }

        $token = $this->tokenIssuer->issue($user);

        return new AuthResult(
            token: $token['token'],
            expiresAt: $token['expires_at'],
            expiresIn: $token['expires_in'],
            user: $user,
        );
    }
}
