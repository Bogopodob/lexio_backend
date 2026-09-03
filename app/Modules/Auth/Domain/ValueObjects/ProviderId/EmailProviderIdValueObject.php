<?php

namespace App\Modules\Auth\Domain\ValueObjects\ProviderId;

use App\Modules\Auth\Domain\Enums\AuthProviderEnum;
use InvalidArgumentException;
use Override;
use RuntimeException;

final class EmailProviderIdValueObject extends ProviderIdValueObject
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    #[Override]
    protected function validate(mixed $value): void
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Email must be a string');
        }

        $value = $this->normalize($value);

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }

        $this->value = $value;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    #[Override]
    public function value(): string
    {
        return $this->value;
    }

    #[Override]
    public function providerType(): AuthProviderEnum
    {
        return AuthProviderEnum::Email;
    }

    public function getDomain(): string
    {
        $position = strpos($this->value, '@');

        if ($position === false) {
            throw new RuntimeException('Invalid email format: @ not found');
        }

        return substr($this->value, $position + 1);
    }

    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
