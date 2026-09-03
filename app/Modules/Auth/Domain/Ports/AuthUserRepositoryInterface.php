<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\Entities\AuthUser;
use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;

interface AuthUserRepositoryInterface
{
    public function findById(string $userId): ?AuthUser;

    public function findByEmail(EmailProviderIdValueObject $email): ?AuthUser;

    public function createWithEmail(
        string $name,
        EmailProviderIdValueObject $email,
        ?PasswordValueObject $password = null,
    ): AuthUser;
}
