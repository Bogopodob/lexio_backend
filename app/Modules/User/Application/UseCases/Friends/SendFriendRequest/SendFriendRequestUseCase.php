<?php

namespace App\Modules\User\Application\UseCases\Friends\SendFriendRequest;

use App\Modules\User\Domain\Entities\Friendship;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;

final readonly class SendFriendRequestUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
        private UserAccountRepositoryInterface $users,
    ) {}

    /**
     * @return array{status: string, friendship: Friendship}|null null = target not found or self-request
     */
    public function handle(SendFriendRequestCommand $command): ?array
    {
        $addresseeId = $command->addresseeId;

        if ($addresseeId === null && $command->email !== null) {
            $found = $this->users->findByEmail($command->email);
            $addresseeId = $found?->id;
        }

        if ($addresseeId === null || $addresseeId === $command->userId) {
            return null;
        }

        if (! $this->users->findById($addresseeId)) {
            return null;
        }

        $existing = $this->friendships->findBetween($command->userId, $addresseeId);

        if ($existing) {
            // Cross request: incoming pending becomes friendship at once.
            if ($existing->status === 'pending' && $existing->requesterId !== $command->userId) {
                $accepted = $this->friendships->save(new Friendship(
                    id: $existing->id,
                    requesterId: $existing->requesterId,
                    addresseeId: $existing->addresseeId,
                    status: 'accepted',
                ));

                return ['status' => 'accepted', 'friendship' => $accepted];
            }

            return ['status' => $existing->status, 'friendship' => $existing];
        }

        $created = $this->friendships->save(new Friendship(
            id: '',
            requesterId: $command->userId,
            addresseeId: $addresseeId,
            status: 'pending',
        ));

        return ['status' => 'pending', 'friendship' => $created];
    }
}
