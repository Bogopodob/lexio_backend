<?php

namespace App\Modules\User\Application\UseCases\Friends\ListFriendRequests;

use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;

final readonly class ListFriendRequestsUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
        private UserAccountRepositoryInterface $users,
    ) {}

    /**
     * @return list<array{id: string, user_id: string, name: ?string, direction: string}>
     */
    public function handle(ListFriendRequestsCommand $command): array
    {
        $rows = $command->direction === 'outgoing'
            ? $this->friendships->pendingOutgoing($command->userId)
            : $this->friendships->pendingIncoming($command->userId);

        $result = [];

        foreach ($rows as $row) {
            $otherId = $row->requesterId === $command->userId ? $row->addresseeId : $row->requesterId;
            $account = $this->users->findById($otherId);

            $result[] = [
                'id' => $row->id,
                'user_id' => $otherId,
                'name' => $account?->name,
                'direction' => $command->direction,
            ];
        }

        return $result;
    }
}
