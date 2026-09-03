<?php

namespace App\Modules\Auth\Application\UseCases\Auth\Logout;

use App\Modules\Auth\Domain\Ports\AccessTokenVerifierInterface;
use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\RevokedTokenStoreInterface;

final readonly class LogoutUseCase
{
    public function __construct(
        private AccessTokenVerifierInterface $verifier,
        private RevokedTokenStoreInterface $revokedTokens,
    ) {}

    /**
     * Revoke the token so it stops working immediately.
     * Returns false when the token is already invalid.
     */
    public function handle(LogoutCommand $command): bool
    {
        if ($command->token === '') {
            return false;
        }

        $verified = $this->verifier->verify($command->token);

        if ($verified === null) {
            return false;
        }

        $this->revokedTokens->revoke(hash('sha256', $command->token), $verified->expiresAt);

        return true;
    }
}
