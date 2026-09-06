<?php

namespace App\Modules\Library\Application\UseCases\SharedWithMe;

final readonly class SharedWithMeCommand
{
    public function __construct(
        public string $userId,
        public string $locale = 'ru',
    ) {}
}
