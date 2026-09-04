<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\UserGoal;
use Illuminate\Http\Resources\Json\JsonResource;

final class UserGoalResource extends JsonResource
{
    public function __construct(private readonly UserGoal $goal)
    {
        parent::__construct($goal);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->goal->id,
            'profile_id' => $this->goal->profileId,
            'title' => $this->goal->title,
            'desc' => $this->goal->desc,
            'color' => $this->goal->color,
            'progress' => $this->goal->progress,
            'sort' => $this->goal->sort,
        ];
    }
}
