<?php

namespace App\Modules\Auth\Application\UseCases\Auth\LoginByEmailPassword;

use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;

final readonly class LoginByEmailPasswordCommand
{
    public function __construct(
        public EmailProviderIdValueObject $email,
        public PasswordValueObject $password,
    ) {}
}
