<?php

namespace App\Shared\Core\Domain\Enum;

enum LanguageEnum: string
{
    use EnumHelper;

    case Ru = 'Русский';
    case En = 'English';
    case De = 'Deutsch';
    case Fr = 'Français';
    case Es = 'Español';

    public static function supportList(): array
    {
        return [
            self::Ru->name,
            self::En->name,
        ];
    }
}
