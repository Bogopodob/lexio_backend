<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\ListCategoriesWithProgress\ListCategoriesWithProgressCommand;
use App\Modules\Learning\Application\UseCases\ListCategoriesWithProgress\ListCategoriesWithProgressUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListCategoriesWithProgressTake
{
    public function __construct(
        private ListCategoriesWithProgressUseCase $useCase,
    ) {}

    public function handle(string $userId, string $profileId, Request $request): JsonResponse
    {
        $locale = (string) $request->query('locale', 'ru');

        $result = $this->useCase->handle(
            new ListCategoriesWithProgressCommand(
                profileId: $profileId,
                userId: $userId,
                type: $request->query('type'),
                locale: in_array($locale, ['ru', 'en'], true) ? $locale : 'ru',
            )
        );

        if ($result === null) {
            return response()->json(['success' => false, 'message' => 'Profile not found'], 404);
        }

        return LearningResponseResource::make($result)->response();
    }
}
