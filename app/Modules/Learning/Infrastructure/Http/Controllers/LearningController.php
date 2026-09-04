<?php

namespace App\Modules\Learning\Infrastructure\Http\Controllers;

use App\Modules\Learning\Infrastructure\Http\Requests\SaveGoalRequest;
use App\Modules\Learning\Infrastructure\Http\Requests\StartLearningRequest;
use App\Modules\Learning\Infrastructure\Http\Requests\SubmitReviewRequest;
use App\Modules\Learning\Infrastructure\Http\Requests\UpdateProfileRequest;
use App\Modules\Learning\Infrastructure\Http\Takes\DeleteGoalTake;
use App\Modules\Learning\Infrastructure\Http\Takes\DueReviewsTake;
use App\Modules\Learning\Infrastructure\Http\Takes\EvaluateAchievementsTake;
use App\Modules\Learning\Infrastructure\Http\Takes\GetStatsTake;
use App\Modules\Learning\Infrastructure\Http\Takes\ListAchievementsTake;
use App\Modules\Learning\Infrastructure\Http\Takes\ListGoalsTake;
use App\Modules\Learning\Infrastructure\Http\Takes\ListProfilesTake;
use App\Modules\Learning\Infrastructure\Http\Takes\SaveGoalTake;
use App\Modules\Learning\Infrastructure\Http\Takes\StartLearningTake;
use App\Modules\Learning\Infrastructure\Http\Takes\SubmitReviewTake;
use App\Modules\Learning\Infrastructure\Http\Takes\UpdateProfileTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class LearningController extends Controller
{
    public function __construct(
        private readonly StartLearningTake $startLearningTake,
        private readonly ListProfilesTake $listProfilesTake,
        private readonly UpdateProfileTake $updateProfileTake,
        private readonly SubmitReviewTake $submitReviewTake,
        private readonly DueReviewsTake $dueReviewsTake,
        private readonly GetStatsTake $getStatsTake,
        private readonly ListGoalsTake $listGoalsTake,
        private readonly SaveGoalTake $saveGoalTake,
        private readonly DeleteGoalTake $deleteGoalTake,
        private readonly ListAchievementsTake $listAchievementsTake,
        private readonly EvaluateAchievementsTake $evaluateAchievementsTake,
    ) {}

    public function start(string $userId, StartLearningRequest $request): JsonResponse
    {
        return $this->startLearningTake->handle($userId, $request);
    }

    public function index(string $userId): JsonResponse
    {
        return $this->listProfilesTake->handle($userId);
    }

    public function update(string $userId, string $profileId, UpdateProfileRequest $request): JsonResponse
    {
        return $this->updateProfileTake->handle($userId, $profileId, $request);
    }

    public function review(string $userId, string $profileId, SubmitReviewRequest $request): JsonResponse
    {
        return $this->submitReviewTake->handle($userId, $profileId, $request);
    }

    public function due(string $userId, string $profileId, Request $request): JsonResponse
    {
        return $this->dueReviewsTake->handle($userId, $profileId, $request);
    }

    public function stats(string $userId, string $profileId): JsonResponse
    {
        return $this->getStatsTake->handle($userId, $profileId);
    }

    public function goals(string $userId, string $profileId): JsonResponse
    {
        return $this->listGoalsTake->handle($userId, $profileId);
    }

    public function storeGoal(string $userId, string $profileId, SaveGoalRequest $request): JsonResponse
    {
        return $this->saveGoalTake->handle($userId, $profileId, $request);
    }

    public function updateGoal(string $userId, string $profileId, string $goalId, SaveGoalRequest $request): JsonResponse
    {
        return $this->saveGoalTake->handle($userId, $profileId, $request, $goalId);
    }

    public function destroyGoal(string $userId, string $profileId, string $goalId): JsonResponse
    {
        return $this->deleteGoalTake->handle($userId, $profileId, $goalId);
    }

    public function achievements(string $userId, string $profileId): JsonResponse
    {
        return $this->listAchievementsTake->handle($userId, $profileId);
    }

    public function evaluateAchievements(string $userId, string $profileId): JsonResponse
    {
        return $this->evaluateAchievementsTake->handle($userId, $profileId);
    }
}
