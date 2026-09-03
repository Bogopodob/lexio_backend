<?php

namespace App\Modules\Auth\Application\DTO;

use App\Modules\Auth\Domain\Entities\AuthUser;
use DateTimeImmutable;

final readonly class AuthResult
{
    public function __construct(
        public string $token,
        public DateTimeImmutable $expiresAt,
        public int $expiresIn,
        public AuthUser $user,
    ) {}
}
