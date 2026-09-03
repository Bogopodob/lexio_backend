<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Shared\Laravel\Infrastructure\Security\Jwt\Contracts\RevokedTokenStoreInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final class CacheRevokedTokenStore implements RevokedTokenStoreInterface
{
    public function revoke(string $tokenHash, DateTimeInterface $expiresAt): void
    {
        Cache::put($this->cacheKey($tokenHash), true, $expiresAt);
    }

    public function isRevoked(string $tokenHash): bool
    {
        return (bool) Cache::get($this->cacheKey($tokenHash), false);
    }

    private function cacheKey(string $tokenHash): string
    {
        return 'auth:revoked:'.$tokenHash;
    }
}
