<?php

namespace App\Modules\User\Application\UseCases\Friends\RemoveFriend;

use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class RemoveFriendUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
    ) {}

    public function handle(RemoveFriendCommand $command): bool
    {
        $row = $this->friendships->find($command->friendshipId);

        if (! $row) {
            return false;
        }

        if ($row->requesterId !== $command->userId && $row->addresseeId !== $command->userId) {
            return false;
        }

        $this->friendships->delete($command->friendshipId);

        return true;
    }
}
