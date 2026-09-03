<?php

namespace App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts;

use DateTimeInterface;

interface RevokedTokenStoreInterface
{
    /**
     * Mark a token (by its hash) as revoked until it naturally expires.
     */
    public function revoke(string $tokenHash, DateTimeInterface $expiresAt): void;

    public function isRevoked(string $tokenHash): bool;
}
