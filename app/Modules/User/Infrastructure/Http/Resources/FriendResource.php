<?php

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\User\Domain\Entities\FriendWithProfile;
use Illuminate\Http\Resources\Json\JsonResource;

final class FriendResource extends JsonResource
{
    public function __construct(private readonly FriendWithProfile $friend)
    {
        parent::__construct($friend);
    }

    public function toArray($request): array
    {
        return [
            'user_id' => $this->friend->userId,
            'name' => $this->friend->name,
            'avatar' => $this->friend->avatar,
            'level' => $this->friend->level,
            'streak_days' => $this->friend->streakDays,
            'friendship_id' => $this->friend->friendshipId,
        ];
    }
}
