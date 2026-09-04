<?php

namespace App\Modules\User\Application\UseCases\Friends\ListFriends;

use App\Modules\User\Domain\Entities\FriendWithProfile;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class ListFriendsUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
    ) {}

    /**
     * @return list<FriendWithProfile>
     */
    public function handle(ListFriendsCommand $command): array
    {
        return $this->friendships->friendsOf($command->userId);
    }
}
