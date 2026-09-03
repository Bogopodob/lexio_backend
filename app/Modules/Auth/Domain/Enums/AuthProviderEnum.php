<?php

namespace App\Modules\Auth\Domain\Enums;

enum AuthProviderEnum: string
{
    case Phone = 'phone';
    case Email = 'email';
    case Google = 'google';
    case Vk = 'vk';
    case Yandex = 'yandex';

    public function isOAuth(): bool
    {
        return match ($this) {
            self::Vk, self::Google, self::Yandex => true,
            default => false,
        };
    }
}
