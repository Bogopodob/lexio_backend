<?php

namespace App\Modules\Auth\Application\UseCases\Auth\VerifyAccessToken;

use App\Modules\Auth\Application\DTO\VerifiedAccessTokenResult;
use App\Modules\Auth\Application\Exceptions\InvalidAccessTokenException;
use App\Modules\Auth\Domain\Ports\AccessTokenVerifierInterface;
use App\Modules\Auth\Domain\Ports\AuthUserRepositoryInterface;

final readonly class VerifyAccessTokenUseCase
{
    public function __construct(
        private AccessTokenVerifierInterface $accessTokenVerifier,
        private AuthUserRepositoryInterface $users,
    ) {}

    public function handle(VerifyAccessTokenCommand $command): VerifiedAccessTokenResult
    {
        $verifiedToken = $this->accessTokenVerifier->verify($command->token);

        if ($verifiedToken === null) {
            throw new InvalidAccessTokenException('Invalid or expired access token');
        }

        $user = $this->users->findById($verifiedToken->subject);
        if ($user === null) {
            throw new InvalidAccessTokenException('Token subject user not found');
        }

        return new VerifiedAccessTokenResult(
            subject: $user->id,
            email: $user->email,
            name: $user->name,
            expiresAt: $verifiedToken->expiresAt,
        );
    }
}
