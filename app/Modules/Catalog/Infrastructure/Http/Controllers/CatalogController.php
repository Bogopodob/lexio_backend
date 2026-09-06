<?php

namespace App\Modules\Catalog\Infrastructure\Http\Controllers;

use App\Modules\Catalog\Infrastructure\Http\Requests\SaveCategoryRequest;
use App\Modules\Catalog\Infrastructure\Http\Requests\SearchEntriesRequest;
use App\Modules\Catalog\Infrastructure\Http\Takes\GetEntryTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\ListCategoriesTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\ListLanguagesTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\QuizRoundTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\SaveCategoryTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\SearchEntriesTake;
use App\Modules\Catalog\Infrastructure\Http\Takes\WordOfDayTake;
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
        private readonly WordOfDayTake $wordOfDayTake,
        private readonly QuizRoundTake $quizRoundTake,
        private readonly SaveCategoryTake $saveCategoryTake,
    ) {}

    public function languages(): JsonResponse
    {
        return $this->listLanguagesTake->handle();
    }

    public function categories(Request $request): JsonResponse
    {
        return $this->listCategoriesTake->handle($request);
    }

    public function myCategories(Request $request): JsonResponse
    {
        return $this->listCategoriesTake->mine($request);
    }

    public function search(SearchEntriesRequest $request): JsonResponse
    {
        return $this->searchEntriesTake->handle($request);
    }

    public function show(string $entryId): JsonResponse
    {
        return $this->getEntryTake->handle($entryId);
    }

    public function wordOfDay(Request $request): JsonResponse
    {
        return $this->wordOfDayTake->handle($request);
    }

    public function storeCategory(SaveCategoryRequest $request): JsonResponse
    {
        return $this->saveCategoryTake->store(
            (string) $request->attributes->get('auth_user_id'),
            $request,
        );
    }

    public function updateCategory(SaveCategoryRequest $request, string $categoryId): JsonResponse
    {
        return $this->saveCategoryTake->update(
            (string) $request->attributes->get('auth_user_id'),
            $categoryId,
            $request,
        );
    }

    public function destroyCategory(Request $request, string $categoryId): JsonResponse
    {
        return $this->saveCategoryTake->destroy(
            (string) $request->attributes->get('auth_user_id'),
            $categoryId,
        );
    }

    public function quizRound(Request $request): JsonResponse
    {
        return $this->quizRoundTake->handle($request);
    }
}
