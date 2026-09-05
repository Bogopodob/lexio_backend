<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\ListSessions\ListSessionsCommand;
use App\Modules\Learning\Application\UseCases\ListSessions\ListSessionsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use App\Modules\Learning\Infrastructure\Http\Resources\StudySessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListSessionsTake
{
    public function __construct(
        private ListSessionsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new ListSessionsCommand(
                profileId: $profileId,
                userId: $userId,
                limit: max(1, min(50, (int) $request->query('limit', 10))),
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = array_map(fn ($s) => StudySessionResource::make($s)->resolve(request()), $result);

        return LearningResponseResource::make($data)->response();
    }
}
