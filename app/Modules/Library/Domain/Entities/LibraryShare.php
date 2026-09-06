<?php

namespace App\Modules\Library\Domain\Entities;

final readonly class LibraryShare
{
    public function __construct(
        public string $id,
        public string $ownerUserId,
        public string $friendUserId,
        public string $categoryId,
        public ?string $friendName = null,
    ) {}
}
