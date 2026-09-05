<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\SaveWordHint\SaveWordHintCommand;
use App\Modules\Learning\Application\UseCases\SaveWordHint\SaveWordHintUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\SaveWordHintRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class SaveWordHintTake
{
    public function __construct(
        private SaveWordHintUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, SaveWordHintRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new SaveWordHintCommand(
                profileId: $profileId,
                userId: $userId,
                learnableType: (string) $request->validated('learnable_type'),
                learnableId: (string) $request->validated('learnable_id'),
                hint: (string) ($request->validated('own_hint') ?? ''),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        return LearningResponseResource::make($result)->response();
    }
}
