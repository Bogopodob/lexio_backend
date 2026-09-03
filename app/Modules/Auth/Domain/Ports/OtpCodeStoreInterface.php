<?php

namespace App\Modules\Auth\Domain\Ports;

interface OtpCodeStoreInterface
{
    /**
     * Store OTP code with TTL
     */
    public function put(string $key, string $code, int $ttlSeconds): void;

    /**
     * Verify OTP code and remove it if valid
     */
    public function verifyAndForget(string $key, string $code): bool;
}
