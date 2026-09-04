<?php

namespace App\Modules\User\Application\UseCases\Friends\ListFriendRequests;

final readonly class ListFriendRequestsCommand
{
    public function __construct(
        public string $userId,
        public string $direction = 'incoming',
    ) {}
}
