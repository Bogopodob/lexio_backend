<?php

namespace App\Modules\Auth\Domain\ValueObjects;

use App\Shared\Core\Domain\ValueObject\AbstractValueObject;
use InvalidArgumentException;
use Override;

final class PasswordValueObject extends AbstractValueObject
{
    private function __construct(string $value, private readonly bool $hashed = false)
    {
        parent::__construct($value);
    }

    public static function fromPlainText(string $value): self
    {
        return new self($value, false);
    }

    public static function fromHash(string $value): self
    {
        return new self($value, true);
    }

    #[Override]
    protected function validate(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Password must be a string');
        }

        if ($value === '') {
            throw new InvalidArgumentException('Password cannot be empty');
        }

    }

    #[Override]
    public function value(): string
    {
        return $this->value;
    }

    public function isHashed(): bool
    {
        return $this->hashed;
    }

    public function toHash(): self
    {
        if ($this->hashed) {
            return $this;
        }

        $hashedValue = password_hash($this->value, PASSWORD_DEFAULT);
        if (! is_string($hashedValue) || $hashedValue === '') {
            throw new InvalidArgumentException('Failed to hash password');
        }

        return self::fromHash($hashedValue);
    }

    public function verify(string|self $password): bool
    {
        if (is_string($password)) {
            if (! $this->hashed) {
                return hash_equals($this->value, $password);
            }

            return password_verify($password, $this->value);
        }

        if (! $this->hashed && ! $password->hashed) {
            return hash_equals($this->value, $password->value());
        }

        if ($this->hashed && ! $password->hashed) {
            return password_verify($password->value(), $this->value);
        }

        if (! $this->hashed && $password->hashed) {
            return password_verify($this->value, $password->value());
        }

        return hash_equals($this->value, $password->value());
    }
}
