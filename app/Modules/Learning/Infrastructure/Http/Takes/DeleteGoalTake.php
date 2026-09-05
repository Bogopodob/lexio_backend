<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\DeleteGoal\DeleteGoalCommand;
use App\Modules\Learning\Application\UseCases\DeleteGoal\DeleteGoalUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class DeleteGoalTake
{
    public function __construct(
        private DeleteGoalUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, string $goalId): JsonResponse
    {
        $deleted = $this->useCase->handle(new DeleteGoalCommand($profileId, $userId, $goalId));

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.goal.not_found')], 404);
        }

        return LearningResponseResource::make(['deleted' => true])->response();
    }
}
