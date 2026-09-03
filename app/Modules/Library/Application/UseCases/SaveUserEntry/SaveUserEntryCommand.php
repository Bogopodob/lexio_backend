<?php

namespace App\Modules\Library\Application\UseCases\SaveUserEntry;

use App\Modules\Library\Domain\Entities\UserEntryTranslation;

final readonly class SaveUserEntryCommand
{
    /**
     * @param  list<UserEntryTranslation>  $translations
     */
    public function __construct(
        public string $userId,
        public ?string $categoryId = null,
        public ?string $imagePath = null,
        public array $translations = [],
    ) {}
}
