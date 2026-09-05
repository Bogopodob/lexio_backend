<?php

namespace App\Modules\User\Application\UseCases\Friends\ListLeaderboard;

final readonly class ListLeaderboardCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
