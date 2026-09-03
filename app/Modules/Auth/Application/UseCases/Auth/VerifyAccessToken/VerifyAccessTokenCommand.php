<?php

namespace App\Modules\Auth\Application\UseCases\Auth\VerifyAccessToken;

final readonly class VerifyAccessTokenCommand
{
    public function __construct(
        public string $token,
    ) {}
}
