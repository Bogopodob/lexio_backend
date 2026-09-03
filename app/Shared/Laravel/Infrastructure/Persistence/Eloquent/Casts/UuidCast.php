<?php

namespace App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Casts;

use App\Shared\Core\Domain\ValueObject\UuidValueObject;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @template T of UuidValueObject
 *
 * @implements CastsAttributes<T|null, string|null>
 */
abstract class UuidCast implements CastsAttributes
{
    /**
     * @return class-string<T>
     */
    abstract protected function valueObjectClass(): string;

    /**
     * @return T|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?UuidValueObject
    {
        if ($value === null) {
            return null;
        }

        $class = $this->valueObjectClass();

        return new $class($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $class = $this->valueObjectClass();

        if ($value instanceof $class) {
            return $value->value();
        }

        if (is_string($value)) {
            return new $class($value)->value();
        }

        throw new InvalidArgumentException("Invalid value for {$key}");
    }
}
