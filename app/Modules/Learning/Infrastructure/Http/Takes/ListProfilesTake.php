<?php

namespace App\Modules\Learning\Infrastructure\Http\Takes;

use App\Modules\Learning\Application\UseCases\ListProfiles\ListProfilesCommand;
use App\Modules\Learning\Application\UseCases\ListProfiles\ListProfilesUseCase;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageProfileResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LanguageStatResource;
use App\Modules\Learning\Infrastructure\Http\Resources\LearningResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class ListProfilesTake
{
    public function __construct(
        private ListProfilesUseCase $useCase,
    ) {}

    public function handle(string $userId): JsonResponse
    {
        $result = $this->useCase->handle(new ListProfilesCommand($userId));

        $data = array_map(function ($item) {
            $row = LanguageProfileResource::make($item->profile)->resolve(request());

            if ($item->stat) {
                $row['stat'] = LanguageStatResource::make($item->stat)->resolve(request());
            }

            return $row;
        }, $result);

        return LearningResponseResource::make($data)->response();
    }
}
