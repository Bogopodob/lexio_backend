<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\SearchEntries\SearchEntriesCommand;
use App\Modules\Catalog\Application\UseCases\SearchEntries\SearchEntriesUseCase;
use App\Modules\Catalog\Infrastructure\Http\Requests\SearchEntriesRequest;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use Illuminate\Http\JsonResponse;

final readonly class SearchEntriesTake
{
    public function __construct(
        private SearchEntriesUseCase $useCase,
    ) {}

    public function handle(SearchEntriesRequest $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new SearchEntriesCommand(
                languageId: $request->validated('language_id'),
                query: $request->validated('query'),
                level: $request->validated('level'),
                limit: (int) ($request->validated('limit') ?? 20),
            )
        );

        $data = array_map(fn ($h) => [
            'entry_id' => $h->entryId,
            'level' => $h->level,
            'image_path' => $h->imagePath,
            'matched_text' => $h->matchedText,
            'language_id' => $h->languageId,
        ], $result);

        return CatalogResponseResource::make($data)->response();
    }
}
