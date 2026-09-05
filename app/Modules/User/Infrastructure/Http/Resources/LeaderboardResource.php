<?php

namespace App\Modules\User\Infrastructure\Http\Resources;

use App\Modules\User\Domain\Entities\LeaderboardRow;
use Illuminate\Http\Resources\Json\JsonResource;

final class LeaderboardResource extends JsonResource
{
    public function __construct(private readonly LeaderboardRow $row)
    {
        parent::__construct($row);
    }

    public function toArray($request): array
    {
        return [
            'user_id' => $this->row->userId,
            'name' => $this->row->name,
            'avatar' => $this->row->avatar,
            'level' => $this->row->level,
            'streak_days' => $this->row->streakDays,
            'is_self' => $this->row->isSelf,
        ];
    }
}
