<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use Illuminate\Support\Facades\Cache;

final class CacheOtpCodeStore implements OtpCodeStoreInterface
{
    public function put(string $key, string $code, int $ttlSeconds): void
    {
        Cache::put($this->cacheKey($key), password_hash($code, PASSWORD_DEFAULT), $ttlSeconds);
    }

    public function verifyAndForget(string $key, string $code): bool
    {
        $cacheKey = $this->cacheKey($key);
        $hash = Cache::get($cacheKey);

        if (! is_string($hash) || $hash === '') {
            return false;
        }

        $valid = password_verify($code, $hash);
        if ($valid) {
            Cache::forget($cacheKey);
        }

        return $valid;
    }

    private function cacheKey(string $key): string
    {
        return 'auth:otp:'.sha1($key);
    }
}
