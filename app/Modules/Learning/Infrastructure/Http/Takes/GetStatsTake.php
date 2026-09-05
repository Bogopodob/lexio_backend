<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\GetStats\GetStatsCommand;
use App\Modules\Learning\Application\UseCases\GetStats\GetStatsUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageStatResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class GetStatsTake
{
    public function __construct(
        private GetStatsUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId): JsonResponse
    {
        $result = $this->useCase->handle(new GetStatsCommand($profileId, $userId));

        if (! $result) {
            return response()->json(['success' => false, 'message' => __('api.profile.not_found')], 404);
        }

        $data = LanguageStatResource::make($result)->resolve(request());

        return LearningResponseResource::make($data)->response();
    }
}
