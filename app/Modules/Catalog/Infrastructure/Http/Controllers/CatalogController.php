<?php

namespace App\Modules\Catalog\Infrastructure\Http\Controllers;

use App\Modules\Catalog\Infrastructure\Http\Requests\SearchEntriesRequest;
use App\Modules\Catalog\Infrastructure\Http\Takes\GetEntryTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\ListCategoriesTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\ListLanguagesTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\SearchEntriesTake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly ListLanguagesTake $listLanguagesTake,
        private readonly ListCategoriesTake $listCategoriesTake,
        private readonly SearchEntriesTake $searchEntriesTake,
        private readonly GetEntryTake $getEntryTake,
    ) {}

    public function languages(): JsonResponse
    {
        return $this->listLanguagesTake->handle();
    }

    public function categories(Request $request): JsonResponse
    {
        return $this->listCategoriesTake->handle($request);
    }

    public function search(SearchEntriesRequest $request): JsonResponse
    {
        return $this->searchEntriesTake->handle($request);
    }

    public function show(string $entryId): JsonResponse
    {
        return $this->getEntryTake->handle($entryId);
    }
}
