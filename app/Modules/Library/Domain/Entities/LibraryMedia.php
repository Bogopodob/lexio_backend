<?php

namespace App\Modules\Library\Domain\Entities;

final readonly class LibraryMedia
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $kind,
        public string $path,
        public string $mime,
        public int $bytes,
    ) {}
}
