<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\GetAvailability\GetAvailabilityCommand;
use App\Modules\Learning\Application\UseCases\GetAvailability\GetAvailabilityUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetAvailabilityTake
{
    public function __construct(
        private GetAvailabilityUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new GetAvailabilityCommand(
                profileId: $profileId,
                userId: $userId,
                categoryId: $request->query('category_id'),
                level: $request->query('level'),
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        return LearningResponseResource::make($result)->response();
    }
}
