<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\LanguageStat;
use Illuminate\Http\Resources\Json\JsonResource;

final class LanguageStatResource extends JsonResource
{
    public function __construct(private readonly LanguageStat $stat)
    {
        parent::__construct($stat);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->stat->id,
            'profile_id' => $this->stat->profileId,
            'words_learned' => $this->stat->wordsLearned,
            'streak_days' => $this->stat->streakDays,
            'best_streak' => $this->stat->bestStreak,
            'xp' => $this->stat->xp,
            'accuracy' => $this->stat->accuracy,
            'last_activity_at' => $this->stat->lastActivityAt,
        ];
    }
}
