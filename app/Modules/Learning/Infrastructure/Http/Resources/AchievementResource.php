<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\AchievementWithProgress;
use Illuminate\Http\Resources\Json\JsonResource;

final class AchievementResource extends JsonResource
{
    public function __construct(private readonly AchievementWithProgress $row)
    {
        parent::__construct($row);
    }

    public function toArray($request): array
    {
        $a = $this->row->achievement;

        return [
            'id' => $a->id,
            'code' => $a->code,
            'title' => $a->title,
            'desc' => $a->desc,
            'condition' => $a->condition,
            'rarity' => $a->rarity,
            'color' => $a->color,
            'reward_xp' => $a->rewardXp,
            'target' => $a->ruleTarget,
            'progress' => $this->row->progress,
            'unlocked' => $this->row->unlocked,
            'unlocked_at' => $this->row->unlockedAt,
        ];
    }
}
