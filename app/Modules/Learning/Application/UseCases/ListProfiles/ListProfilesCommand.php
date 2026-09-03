<?php

namespace App\Modules\Learning\Application\UseCases\ListProfiles;

final readonly class ListProfilesCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
