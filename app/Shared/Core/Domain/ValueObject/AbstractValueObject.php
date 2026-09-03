<?php

namespace App\Shared\Core\Domain\ValueObject;

use Override;

/**
 * @template T
 *
 * @implements ValueObjectInterface<T>
 */
abstract class AbstractValueObject implements ValueObjectInterface
{
    /**
     * @param  T  $value
     */
    public function __construct(
        protected $value
    ) {
        $this->validate($value);
    }

    /**
     * @return T
     */
    #[Override]
    public function value()
    {
        return $this->value;
    }

    #[Override]
    public function equals(?ValueObjectInterface $other): bool
    {
        if (! $other) {
            return false;
        }

        return get_class($this) === get_class($other) && $this->value() === $other->value();
    }

    #[Override]
    public function toString(): string
    {
        try {
            return match (true) {
                is_scalar($this->value) => $this->scalarToString(),
                $this->value === null => '',
                is_array($this->value) => json_encode($this->value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                $this->value instanceof \Stringable => (string) $this->value,
                is_object($this->value) => $this->value::class.' object',
                default => gettype($this->value),
            };
        } catch (\JsonException $e) {
            return 'array';
        }
    }

    private function scalarToString(): string
    {
        return match (true) {
            is_bool($this->value) => $this->value ? 'true' : 'false',
            default => (string) $this->value,
        };
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @param  T  $value
     */
    abstract protected function validate($value): void;
}
