<?php

namespace App\Shared\Laravel\Infrastructure\Security\Jwt\DTO;

use Carbon\Carbon;

final readonly class TokenResultDTO
{
    public function __construct(
        public string $token,
        public Carbon $issuedAt,
        public Carbon $expiresAt,
        public int $expiresIn,
    ) {}
}
