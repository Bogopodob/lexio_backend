<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use App\Modules\Learning\Domain\Entities\StudySession;
use Illuminate\Http\Resources\Json\JsonResource;

final class StudySessionResource extends JsonResource
{
    public function __construct(private readonly StudySession $session)
    {
        parent::__construct($session);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->session->id,
            'profile_id' => $this->session->profileId,
            'source' => $this->session->source,
            'category_id' => $this->session->categoryId,
            'status' => $this->session->status,
            'total' => $this->session->total,
            'answered' => $this->session->answered,
            'correct' => $this->session->correct,
            'xp_earned' => $this->session->xpEarned,
            'started_at' => $this->session->startedAt,
            'finished_at' => $this->session->finishedAt,
        ];
    }
}
