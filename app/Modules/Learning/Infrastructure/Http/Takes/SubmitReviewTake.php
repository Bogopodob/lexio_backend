<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewCommand;
use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\SubmitReviewRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\ReviewProgressResource;
use Illuminate\Http\JsonResponse;

final readonly class SubmitReviewTake
{
    public function __construct(
        private SubmitReviewUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, SubmitReviewRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new SubmitReviewCommand(
                profileId: $profileId,
                userId: $userId,
                learnableType: $request->validated('learnable_type'),
                learnableId: $request->validated('learnable_id'),
                quality: (int) $request->validated('quality'),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = [
            'progress' => ReviewProgressResource::make($result->progress)->resolve(request()),
            'is_new_word' => $result->isNewWord,
            'xp_gained' => $result->xpGained,
            'newly_unlocked' => $result->newlyUnlocked,
        ];

        return LearningResponseResource::make($data)->response();
    }
}
