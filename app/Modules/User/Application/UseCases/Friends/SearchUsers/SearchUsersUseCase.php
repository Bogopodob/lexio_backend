<?php

namespace App\Modules\User\Application\UseCases\Friends\SearchUsers;

use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class SearchUsersUseCase
{
    public function __construct(
        private FriendshipRepositoryInterface $friendships,
    ) {}

    /**
     * @return list<array{user_id: string, name: ?string, email: string, relation: ?string}>
     */
    public function handle(SearchUsersCommand $command): array
    {
        if (mb_strlen(trim($command->query)) < 2) {
            return [];
        }

        $result = [];

        foreach ($this->friendships->searchUsers($command->userId, $command->query, $command->limit) as $row) {
            $link = $this->friendships->findBetween($command->userId, $row['user_id']);

            $result[] = [
                'user_id' => $row['user_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'relation' => $link?->status,
            ];
        }

        return $result;
    }
}
