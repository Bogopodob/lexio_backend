<?php

namespace App\Shared\Core\Domain\ValueObject;

/**
 * @template T
 */
interface ValueObjectInterface
{
    /**
     * @return T
     */
    public function value();

    public function equals(ValueObjectInterface $other): bool;

    public function toString(): string;
}
