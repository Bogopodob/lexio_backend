<?php

namespace App\Modules\Auth\Domain\Entities;

use DateTimeImmutable;

final readonly class VerifiedAccessToken
{
    public function __construct(
        public string $subject,
        public ?string $email,
        public ?string $name,
        public DateTimeImmutable $expiresAt,
    ) {}
}
