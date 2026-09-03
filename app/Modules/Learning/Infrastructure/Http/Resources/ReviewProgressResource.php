<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\ReviewProgress;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReviewProgressResource extends JsonResource
{
    public function __construct(private readonly ReviewProgress $progress)
    {
        parent::__construct($progress);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->progress->id,
            'profile_id' => $this->progress->profileId,
            'learnable_type' => $this->progress->learnableType,
            'learnable_id' => $this->progress->learnableId,
            'easiness_factor' => $this->progress->easinessFactor,
            'interval_days' => $this->progress->intervalDays,
            'repetition' => $this->progress->repetition,
            'quality_last' => $this->progress->qualityLast,
            'next_review_at' => $this->progress->nextReviewAt,
            'last_reviewed_at' => $this->progress->lastReviewedAt,
        ];
    }
}
