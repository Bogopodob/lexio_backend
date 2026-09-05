<?php

namespace App\Modules\Catalog\Infrastructure\Http\Takes;

use App\Modules\Catalog\Application\UseCases\DeleteCategory\DeleteCategoryCommand;
use App\Modules\Catalog\Application\UseCases\DeleteCategory\DeleteCategoryUseCase;
use App\Modules\Catalog\Application\UseCases\SaveCategory\SaveCategoryCommand;
use App\Modules\Catalog\Application\UseCases\SaveCategory\SaveCategoryUseCase;
use App\Modules\Catalog\Infrastructure\Http\Requests\SaveCategoryRequest;
use App\Modules\Catalog\Infrastructure\Http\Resources\CatalogResponseResource;
use App\Modules\Catalog\Infrastructure\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

final readonly class SaveCategoryTake
{
    public function __construct(
        private SaveCategoryUseCase $saveCategory,
        private DeleteCategoryUseCase $deleteCategory,
    ) {}

    public function store(string $userId, SaveCategoryRequest $request): JsonResponse
    {
        $category = $this->saveCategory->handle(new SaveCategoryCommand(
            userId: $userId,
            name: (string) $request->validated('name'),
            locale: (string) ($request->validated('locale') ?? 'ru'),
            color: $request->validated('color'),
            icon: $request->validated('icon'),
            parentId: $request->validated('parent_id'),
        ));

        if (! $category) {
            return response()->json(['success' => false, 'message' => __('api.category.invalid')], 422);
        }

        return CatalogResponseResource::make(
            CategoryResource::make($category)->resolve(request())
        )->response()->setStatusCode(201);
    }

    public function update(string $userId, string $categoryId, SaveCategoryRequest $request): JsonResponse
    {
        $category = $this->saveCategory->handle(new SaveCategoryCommand(
            userId: $userId,
            name: (string) $request->validated('name'),
            locale: (string) ($request->validated('locale') ?? 'ru'),
            categoryId: $categoryId,
            color: $request->validated('color'),
            icon: $request->validated('icon'),
            parentId: $request->validated('parent_id'),
        ));

        if (! $category) {
            return response()->json(['success' => false, 'message' => __('api.category.forbidden')], 403);
        }

        return CatalogResponseResource::make(
            CategoryResource::make($category)->resolve(request())
        )->response();
    }

    public function destroy(string $userId, string $categoryId): JsonResponse
    {
        $deleted = $this->deleteCategory->handle(new DeleteCategoryCommand($userId, $categoryId));

        if (! $deleted) {
            return response()->json(['success' => false, 'message' => __('api.category.forbidden')], 403);
        }

        return CatalogResponseResource::make(['deleted' => true])->response();
    }
}
