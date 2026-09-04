<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\EvaluateAchievements\EvaluateAchievementsCommand;
use App\Modules\Learning\Application\UseCases\EvaluateAchievements\EvaluateAchievementsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class EvaluateAchievementsTake
{
    public function __construct(
        private EvaluateAchievementsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId): JsonResponse
    {
        $result = $this->useCase->handle(new EvaluateAchievementsCommand($profileId, $userId));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $data = array_map(fn ($a) => [
            'code' => $a->code,
            'title' => $a->title,
            'reward_xp' => $a->rewardXp,
        ], $result);

        return LearningResponseResource::make(['unlocked' => $data])->response();
    }
}
