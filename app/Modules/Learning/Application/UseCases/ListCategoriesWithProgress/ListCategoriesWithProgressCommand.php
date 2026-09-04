<?php

namespace App\Modules\Learning\Application\UseCases\ListCategoriesWithProgress;

final readonly class ListCategoriesWithProgressCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public ?string $type = null,
        public string $locale = 'ru',
    ) {}
}
