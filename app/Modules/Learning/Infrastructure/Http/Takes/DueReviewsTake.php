<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\DueReviews\DueReviewsCommand;
use App\Modules\Learning\Application\UseCases\DueReviews\DueReviewsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\ReviewProgressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class DueReviewsTake
{
    public function __construct(
        private DueReviewsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new DueReviewsCommand(
                profileId: $profileId,
                userId: $userId,
                limit: max(1, min(100, (int) $request->query('limit', 20))),
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = array_map(fn ($p) => ReviewProgressResource::make($p)->resolve($request), $result);

        return LearningResponseResource::make($data)->response();
    }
}
