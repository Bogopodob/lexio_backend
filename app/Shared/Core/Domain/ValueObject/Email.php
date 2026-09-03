<?php

namespace App\Shared\Core\Domain\ValueObject;

use InvalidArgumentException;
use Override;

/**
 * @extends AbstractValueObject<string>
 */
final class Email extends AbstractValueObject
{
    public function __construct(string $value)
    {
        parent::__construct($value);
    }

    #[Override]
    protected function validate($value): void
    {
        $value = strtolower(trim($value));

        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email: {$value}");
        }

        $this->value = $value;
    }

    #[Override]
    public function value(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        $position = strpos($this->value, '@');

        if ($position === false) {
            throw new \RuntimeException('Invalid email format: @ not found');
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
