<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\ListUserPhrases\ListUserPhrasesCommand;
use App\Modules\Library\Application\UseCases\ListUserPhrases\ListUserPhrasesUseCase;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserPhraseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListUserPhrasesTake
{
    public function __construct(
        private ListUserPhrasesUseCase $useCase,
    ) {}

    public function handle(string $userId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new ListUserPhrasesCommand(
                userId: $userId,
                languageId: $request->query('language_id'),
            )
        );

        $data = array_map(fn ($p) => UserPhraseResource::make($p)->resolve($request), $result);

        return LibraryResponseResource::make($data)->response();
    }
}
