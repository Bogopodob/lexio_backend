<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\WeeklyActivity\WeeklyActivityCommand;
use App\Modules\Learning\Application\UseCases\WeeklyActivity\WeeklyActivityUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class WeeklyActivityTake
{
    public function __construct(
        private WeeklyActivityUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(new WeeklyActivityCommand(
            profileId: $profileId,
            userId: $userId,
            days: max(1, min(31, (int) $request->query('days', 7))),
        ));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        return LearningResponseResource::make($result)->response();
    }
}
