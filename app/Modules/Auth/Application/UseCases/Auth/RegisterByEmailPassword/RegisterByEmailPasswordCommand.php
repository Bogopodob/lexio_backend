<?php

namespace App\Modules\Auth\Application\UseCases\Auth\RegisterByEmailPassword;

use App\Modules\Auth\Domain\ValueObjects\PasswordValueObject;
use App\Modules\Auth\Domain\ValueObjects\ProviderId\EmailProviderIdValueObject;

final readonly class RegisterByEmailPasswordCommand
{
    public function __construct(
        public EmailProviderIdValueObject $email,
        public PasswordValueObject $password,
        public ?string $name,
    ) {}
}
