<?php

namespace App\Modules\User\Domain\Ports;

use App\Modules\User\Domain\Entities\Friendship;
use App\Modules\User\Domain\Entities\FriendWithProfile;
use App\Modules\User\Domain\Entities\LeaderboardRow;

interface FriendshipRepositoryInterface
{
    public function find(string $id): ?Friendship;

    public function findBetween(string $userA, string $userB): ?Friendship;

    public function findPendingReverse(string $requesterId, string $addresseeId): ?Friendship;

    public function save(Friendship $friendship): Friendship;

    public function delete(string $id): void;

    /**
     * @return list<Friendship>
     */
    public function pendingIncoming(string $userId): array;

    /**
     * @return list<Friendship>
     */
    public function pendingOutgoing(string $userId): array;

    /**
     * @return list<FriendWithProfile>
     */
    public function friendsOf(string $userId): array;

    /**
     * Self + accepted friends ordered by streak (desc), name (asc).
     *
     * @return list<LeaderboardRow>
     */
    public function leaderboard(string $userId): array;

    /**
     * @return list<array{user_id: string, name: ?string, email: string}>
     */
    public function searchUsers(string $excludeUserId, string $query, int $limit): array;
}
