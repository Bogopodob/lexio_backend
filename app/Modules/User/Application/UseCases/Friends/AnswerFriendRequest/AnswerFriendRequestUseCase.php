<?php

namespace App\Modules\User\Application\UseCases\Friends\AnswerFriendRequest;

use App\Modules\User\Domain\Entities\Friendship;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class AnswerFriendRequestUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
    ) {}

    /**
     * Only the addressee can answer, and only a pending request.
     */
    public function handle(AnswerFriendRequestCommand $command): ?Friendship
    {
        $request = $this->friendships->find($command->requestId);

        if (! $request || $request->status !== 'pending' || $request->addresseeId !== $command->userId) {
            return null;
        }

        return $this->friendships->save(new Friendship(
            id: $request->id,
            requesterId: $request->requesterId,
            addresseeId: $request->addresseeId,
            status: $command->accept ? 'accepted' : 'declined',
        ));
    }
}
