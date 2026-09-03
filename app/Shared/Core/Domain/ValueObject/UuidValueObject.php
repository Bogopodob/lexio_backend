<?php

namespace App\Shared\Core\Domain\ValueObject;

use InvalidArgumentException;

class UuidValueObject extends AbstractValueObject
{
    protected function validate($value): void
    {
        if (! is_string($value) || ! $this->isValidUuid($value)) {
            throw new InvalidArgumentException('Value must be a valid UUID string.');
        }
    }

    private function isValidUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
            $value
        ) === 1;
    }
}
