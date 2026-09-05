<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\ListAchievements\ListAchievementsCommand;
use App\Modules\Learning\Application\UseCases\ListAchievements\ListAchievementsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\AchievementResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class ListAchievementsTake
{
    public function __construct(
        private ListAchievementsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId): JsonResponse
    {
        $result = $this->useCase->handle(new ListAchievementsCommand($profileId, $userId));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = array_map(fn ($r) => AchievementResource::make($r)->resolve(request()), $result);

        return LearningResponseResource::make($data)->response();
    }
}
