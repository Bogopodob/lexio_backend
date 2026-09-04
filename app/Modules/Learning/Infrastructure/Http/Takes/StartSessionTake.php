<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\StartSession\StartSessionCommand;
use App\Modules\Learning\Application\UseCases\StartSession\StartSessionUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\StartSessionRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudySessionResource;
use Illuminate\Http\JsonResponse;

final readonly class StartSessionTake
{
    public function __construct(
        private StartSessionUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, StartSessionRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new StartSessionCommand(
                profileId: $profileId,
                userId: $userId,
                source: $request->validated('source') ?? 'mixed',
                categoryId: $request->validated('category_id'),
                level: $request->validated('level'),
                limit: (int) ($request->validated('limit') ?? 20),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Nothing to learn'], 422);
        }

        $data = StudySessionResource::make($result)->resolve(request());

        return LearningResponseResource::make($data)->response()->setStatusCode(201);
    }
}
