<?php

namespace App\Modules\Auth\Domain\Entities;

use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;
use App\Shared\Core\Domain\ValueObject\UuidValueObject;

final readonly class AuthUser
{
    public function __construct(
        public UuidValueObject $id,
        public string $name,
        public EmailProviderIdValueObject $email,
        public ?PasswordValueObject $password = null,
    ) {}
}
