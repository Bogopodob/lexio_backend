<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\SaveGoal\SaveGoalCommand;
use App\Modules\Learning\Application\UseCases\SaveGoal\SaveGoalUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\SaveGoalRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\UserGoalResource;
use Illuminate\Http\JsonResponse;

final readonly class SaveGoalTake
{
    public function __construct(
        private SaveGoalUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, SaveGoalRequest $request, ?string $goalId = null): JsonResponse
    {
        $result = $this->useCase->handle(
            new SaveGoalCommand(
                profileId: $profileId,
                userId: $userId,
                goalId: $goalId,
                title: $request->validated('title'),
                desc: $request->validated('desc'),
                color: $request->validated('color'),
                progress: $request->validated('progress') !== null ? (int) $request->validated('progress') : null,
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.goal.not_found_or_limit')], 422);
        }

        $data = UserGoalResource::make($result)->resolve(request());

        $status = $goalId === null ? 201 : 200;

        return LearningResponseResource::make($data)->response()->setStatusCode($status);
    }
}
