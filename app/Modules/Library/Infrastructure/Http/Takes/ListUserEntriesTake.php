<?php

namespace App\Modules\Library\Infrastructure\Http\Takes;

use App\Modules\Library\Application\UseCases\ListUserEntries\ListUserEntriesCommand;
use App\Modules\Library\Application\UseCases\ListUserEntries\ListUserEntriesUseCase;
use App\Modules\Library\Infrastructure\Http\Resources\LibraryResponseResource;
use App\Modules\Library\Infrastructure\Http\Resources\UserEntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListUserEntriesTake
{
    public function __construct(
        private ListUserEntriesUseCase $useCase,
    ) {}

    public function handle(string $userId, Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new ListUserEntriesCommand(
                userId: $userId,
                languageId: $request->query('language_id') ?: null,
                categoryId: $request->query('category_id') ?: null,
            )
        );

        $data = array_map(fn ($e) => UserEntryResource::make($e)->resolve($request), $result);

        return LibraryResponseResource::make($data)->response();
    }
}
