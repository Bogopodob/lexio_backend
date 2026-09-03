<?php

namespace App\Modules\Library\Domain\Entities;

final readonly class UserEntry
{
    /**
     * @param  list<UserEntryTranslation>  $translations
     */
    public function __construct(
        public string $id,
        public string $userId,
        public ?string $categoryId,
        public ?string $imagePath,
        public array $translations = [],
    ) {}
}
