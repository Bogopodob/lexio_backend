<?php

namespace App\Modules\Auth\Domain\ValueObjects\ProviderId;

use App\Modules\Auth\Domain\Enums\AuthProviderEnum;
use App\Shared\Core\Domain\ValueObject\AbstractValueObject;

abstract class ProviderIdValueObject extends AbstractValueObject
{
    abstract public function providerType(): AuthProviderEnum;

    public function isEmail(): bool
    {
        return $this->providerType() === AuthProviderEnum::Email;
    }
}
