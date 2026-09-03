<?php

namespace App\Shared\Core\Domain\Enum;

trait EnumHelper
{
    public static function listValues(): array
    {
        return array_map(
            static fn (self $case) => $case->value,
            self::cases()
        );
    }

    public static function listNames(): array
    {
        return array_map(
            static fn (self $case) => $case->name,
            self::cases()
        );
    }

    public static function toArray(): array
    {
        foreach (self::cases() as $case) {
            $result[$case->name] = $case->value;
        }

        return $result ?? [];
    }

    public static function options(): array
    {
        foreach (self::cases() as $case) {
            $result[$case->value] = $case->name;
        }

        return $result ?? [];
    }

    public static function fromValue(string|int $value): ?self
    {
        return array_find(self::cases(), fn ($case) => $case->value === $value);
    }

    public static function fromName(string $value): ?self
    {
        return array_find(self::cases(), fn ($case) => $case->name === $value);
    }
}
