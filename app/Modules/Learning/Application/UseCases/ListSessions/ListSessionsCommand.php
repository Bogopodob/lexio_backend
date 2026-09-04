<?php

namespace App\Modules\Learning\Application\UseCases\ListSessions;

final readonly class ListSessionsCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public int $limit = 10,
    ) {}
}
