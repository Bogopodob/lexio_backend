<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Modules\Auth\Domain\OtpCheckResult;
use App\Modules\Auth\Domain\Ports\OtpCodeStoreInterface;
use Illuminate\Support\Facades\Cache;

final class CacheOtpCodeStore implements OtpCodeStoreInterface
{
    public function put(string $key, string $code, int $ttlSeconds): void
    {
        Cache::put($this->cacheKey($key), password_hash($code, PASSWORD_DEFAULT), $ttlSeconds);
        Cache::forget($this->attemptsKey($key));
    }

    public function attempt(string $key, string $code): OtpCheckResult
    {
        $cacheKey = $this->cacheKey($key);
        $hash = Cache::get($cacheKey);

        if (! is_string($hash) || $hash === '') {
            return OtpCheckResult::Missing;
        }

        if (password_verify($code, $hash)) {
            Cache::forget($cacheKey);
            Cache::forget($this->attemptsKey($key));

            return OtpCheckResult::Valid;
        }

        $max = max(1, (int) config('auth_rate_limits.otp_max_attempts', 5));
        $attemptsKey = $this->attemptsKey($key);
        $attempts = (int) Cache::get($attemptsKey, 0) + 1;

        if ($attempts >= $max) {
            // Burn the code: the attacker learned nothing, the user re-requests.
            Cache::forget($cacheKey);
            Cache::forget($attemptsKey);

            return OtpCheckResult::Burned;
        }

        Cache::put($attemptsKey, $attempts, 3600);

        return OtpCheckResult::Invalid;
    }

    private function cacheKey(string $key): string
    {
        return 'auth:otp:'.sha1($key);
    }

    private function attemptsKey(string $key): string
    {
        return 'auth:otp:attempts:'.sha1($key);
    }
}
