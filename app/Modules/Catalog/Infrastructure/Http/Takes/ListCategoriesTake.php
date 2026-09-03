<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\ListCategories\ListCategoriesCommand;
use App\Modules\Catalog\Application\UseCases\ListCategories\ListCategoriesUseCase;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use App\Modules\Catalog\Infrastructure\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class ListCategoriesTake
{
    public function __construct(
        private ListCategoriesUseCase $useCase,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $result = $this->useCase->handle(
            new ListCategoriesCommand(type: $request->query('type'))
        );

        $data = array_map(fn ($c) => CategoryResource::make($c)->resolve($request), $result);

        return CatalogResponseResource::make($data)->response();
    }
}
