<?php

namespace App\Modules\Learning\Application\UseCases\StartSession;

final readonly class StartSessionCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public string $source = 'mixed',
        public ?string $categoryId = null,
        public ?string $level = null,
        public int $limit = 20,
    ) {}
}
