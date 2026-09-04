<?php

namespace App\Modules\User\Application\UseCases\Friends\ListFriends;

final readonly class ListFriendsCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
