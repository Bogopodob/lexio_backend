<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\LanguageProfile;
use Illuminate\Http\Resources\Json\JsonResource;

final class LanguageProfileResource extends JsonResource
{
    public function __construct(private readonly LanguageProfile $profile)
    {
        parent::__construct($profile);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->profile->id,
            'user_id' => $this->profile->userId,
            'target_language_id' => $this->profile->targetLanguageId,
            'native_language_id' => $this->profile->nativeLanguageId,
            'level' => $this->profile->level,
            'daily_goal' => $this->profile->dailyGoal,
            'is_active' => $this->profile->isActive,
        ];
    }
}
