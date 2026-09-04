<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\AnswerCard\AnswerCardCommand;
use App\Modules\Learning\Application\UseCases\AnswerCard\AnswerCardUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\AnswerCardRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\ReviewProgressResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudyCardResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudySessionResource;
use Illuminate\Http\JsonResponse;

final readonly class AnswerCardTake
{
    public function __construct(
        private AnswerCardUseCase $useCase,
    ) {}

    public function handle(string $userId, string $sessionId, AnswerCardRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new AnswerCardCommand(
                sessionId: $sessionId,
                userId: $userId,
                learnableId: $request->validated('learnable_id'),
                quality: (int) $request->validated('quality'),
            )
        );

        if (! $result) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        return LearningResponseResource::make([
            'session' => StudySessionResource::make($result->session)->resolve(request()),
            'progress' => ReviewProgressResource::make($result->review->progress)->resolve(request()),
            'is_new_word' => $result->review->isNewWord,
            'xp_gained' => $result->review->xpGained,
            'newly_unlocked' => $result->review->newlyUnlocked,
            'finished' => $result->finished,
            'next_card' => $result->nextCard ? StudyCardResource::make($result->nextCard)->resolve(request()) : null,
        ])->response();
    }
}
