<?php

namespace App\Modules\User\Application\UseCases\Friends\ListLeaderboard;

use App\Modules\User\Domain\Entities\LeaderboardRow;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class ListLeaderboardUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
    ) {}

    /**
     * @return list<LeaderboardRow>
     */
    public function handle(ListLeaderboardCommand $command): array
    {
        return $this->friendships->leaderboard($command->userId);
    }
}
