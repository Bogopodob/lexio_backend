<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use App\Modules\Learning\Infrastructure\Http\Requests\StartLearningRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageProfileResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageStatResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class StartLearningTake
{
    public function __construct(
        private StartLearningUseCase $useCase,
    ) {}

    public function handle(string $userId, StartLearningRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new StartLearningCommand(
                userId: $userId,
                targetLanguageId: $request->validated('target_language_id'),
                nativeLanguageId: $request->validated('native_language_id'),
                level: $request->validated('level') ?? 'A2',
                dailyGoal: (int) ($request->validated('daily_goal') ?? 10),
            )
        );

        $data = LanguageProfileResource::make($result->profile)->resolve(request());

        if ($result->stat) {
            $data['stat'] = LanguageStatResource::make($result->stat)->resolve(request());
        }

        return LearningResponseResource::make($data)->response()->setStatusCode(201);
    }
}
