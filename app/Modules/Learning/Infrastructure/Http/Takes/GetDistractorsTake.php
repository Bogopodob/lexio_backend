<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;
use App\Modules\Learning\Infrastructure\Http\Requests\GetDistractorsRequest;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class GetDistractorsTake
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
    ) {}

    public function handle(string $userId, string $profileId, GetDistractorsRequest $request): JsonResponse
    {
        // Ownership is enforced by the user.owner middleware; profileId scopes the languages.
        $options = $this->sessions->distractors(
            profileId: $profileId,
            learnableType: (string) $request->validated('learnable_type'),
            learnableId: (string) $request->validated('learnable_id'),
            side: (string) $request->validated('side'),
            categoryId: $request->validated('category_id'),
            count: (int) ($request->validated('count') ?? 3),
        );

        return LearningResponseResource::make(['options' => $options])->response();
    }
}
