<?php

namespace App\Shared\Laravel\Infrastructure\Security\Jwt\DTO;

use App\Shared\Core\Domain\ValueObject\Email;
use Carbon\Carbon;
use Ramsey\Uuid\UuidInterface;

final readonly class TokenPayloadDTO
{
    public function __construct(
        public UuidInterface $id,
        public Email $email,
        public array $abilities,
        public ?string $ipAddress,
        public ?string $userAgent,
        public Carbon $issuedAt,
    ) {}
}
