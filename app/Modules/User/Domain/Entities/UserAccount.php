<?php

namespace App\Modules\User\Domain\Entities;

final readonly class UserAccount
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $passwordHash,
    ) {}
}
