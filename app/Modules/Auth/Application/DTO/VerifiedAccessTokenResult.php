<?php

namespace App\Modules\Auth\Application\DTO;

use DateTimeImmutable;

final readonly class VerifiedAccessTokenResult
{
    public function __construct(
        public string $subject,
        public ?string $email,
        public ?string $name,
        public DateTimeImmutable $expiresAt,
    ) {}
}
