<?php

namespace App\Modules\User\Application\UseCases\Friends\RemoveFriend;

final readonly class RemoveFriendCommand
{
    public function __construct(
        public string $userId,
        public string $friendshipId,
    ) {}
}
