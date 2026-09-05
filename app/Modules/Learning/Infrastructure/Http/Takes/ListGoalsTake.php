<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\ListGoals\ListGoalsCommand;
use App\Modules\Learning\Application\UseCases\ListGoals\ListGoalsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\UserGoalResource;
use Illuminate\Http\JsonResponse;

final readonly class ListGoalsTake
{
    public function __construct(
        private ListGoalsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId): JsonResponse
    {
        $result = $this->useCase->handle(new ListGoalsCommand($profileId, $userId));

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = array_map(fn ($g) => UserGoalResource::make($g)->resolve(request()), $result);

        return LearningResponseResource::make($data)->response();
    }
}
