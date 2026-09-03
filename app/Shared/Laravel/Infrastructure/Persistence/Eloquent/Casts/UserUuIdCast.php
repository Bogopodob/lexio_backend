<?php

namespace App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Casts;

use App\Shared\Core\Domain\ValueObject\UserIdValueObject;

final class UserUuIdCast extends UuidCast
{
    protected function valueObjectClass(): string
    {
        return UserIdValueObject::class;
    }
}
