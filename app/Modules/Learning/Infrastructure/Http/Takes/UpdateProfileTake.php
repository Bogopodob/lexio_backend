<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\UpdateProfile\UpdateProfileCommand;
use App\Modules\Learning\Application\UseCases\UpdateProfile\UpdateProfileUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\UpdateProfileRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageProfileResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class UpdateProfileTake
{
    public function __construct(
        private UpdateProfileUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new UpdateProfileCommand(
                profileId: $profileId,
                userId: $userId,
                level: $request->validated('level'),
                dailyGoal: $request->validated('daily_goal') !== null ? (int) $request->validated('daily_goal') : null,
                isActive: $request->validated('is_active'),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = LanguageProfileResource::make($result)->resolve(request());

        return LearningResponseResource::make($data)->response();
    }
}
