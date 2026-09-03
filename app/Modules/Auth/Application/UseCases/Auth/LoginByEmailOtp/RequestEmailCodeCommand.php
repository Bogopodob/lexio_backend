<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailOtp;

final readonly class RequestEmailCodeCommand
{
    public function __construct(
        public string $email,
        public ?string $locale = null,
    ) {}
}
