<?php

namespace App\Modules\User\Domain\Entities;

final readonly class FriendWithProfile
{
    public function __construct(
        public string $userId,
        public ?string $name,
        public ?string $avatar,
        public ?string $level,
        public int $streakDays,
        public string $friendshipId,
    ) {}
}
