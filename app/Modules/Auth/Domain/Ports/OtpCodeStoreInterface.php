<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\OtpCheckResult;

interface OtpCodeStoreInterface
{
    /**
     * Store OTP code with TTL (resets the wrong-attempt counter)
     */
    public function put(string $key, string $code, int $ttlSeconds): void;

    /**
     * Check a guess: burns the code after too many wrong attempts.
     */
    public function attempt(string $key, string $code): OtpCheckResult;
}
