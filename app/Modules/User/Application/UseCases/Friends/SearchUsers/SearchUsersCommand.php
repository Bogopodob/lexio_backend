<?php

namespace App\Modules\User\Application\UseCases\Friends\SearchUsers;

final readonly class SearchUsersCommand
{
    public function __construct(
        public string $userId,
        public string $query,
        public int $limit = 10,
    ) {}
}
