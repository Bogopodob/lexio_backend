<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\FinishSession\FinishSessionCommand;
use App\Modules\Learning\Application\UseCases\FinishSession\FinishSessionUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudySessionResource;
use Illuminate\Http\JsonResponse;

final readonly class FinishSessionTake
{
    public function __construct(
        private FinishSessionUseCase $useCase,
    ) {}

    public function handle(string $userId, string $sessionId): JsonResponse
    {
        $result = $this->useCase->handle(new FinishSessionCommand($sessionId, $userId));

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        $data = StudySessionResource::make($result)->resolve(request());

        return LearningResponseResource::make($data)->response();
    }
}
