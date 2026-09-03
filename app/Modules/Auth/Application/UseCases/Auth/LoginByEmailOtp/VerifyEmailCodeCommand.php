<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp;

final readonly class VerifyEmailCodeCommand
{
    public function __construct(
        public string $email,
        public string $code,
    ) {}
}
