<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\GetNextCard\GetNextCardCommand;
use App\Modules\Learning\Application\UseCases\GetNextCard\GetNextCardUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\ReviewProgressResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudyCardResource;
use Illuminate\Http\JsonResponse;

final readonly class GetNextCardTake
{
    public function __construct(
        private GetNextCardUseCase $useCase,
    ) {}

    public function handle(string $userId, string $sessionId): JsonResponse
    {
        $result = $this->useCase->handle(new GetNextCardCommand($sessionId, $userId));

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.session.not_found')], 404);
        }

        return LearningResponseResource::make([
            'session_id' => $result['session_id'],
            'position' => $result['position'],
            'total' => $result['total'],
            'answered' => $result['answered'],
            'card' => StudyCardResource::make($result['card'])->resolve(request()),
            'progress' => $result['progress']
                ? ReviewProgressResource::make($result['progress'])->resolve(request())
                : null,
        ])->response();
    }
}
