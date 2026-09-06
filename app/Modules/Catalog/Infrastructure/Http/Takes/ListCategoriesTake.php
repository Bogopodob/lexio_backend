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
        $locale = (string) $request->query('locale', 'ru');

        $result = $this->useCase->handle(
            new ListCategoriesCommand(
                type: $request->query('type'),
                locale: in_array($locale, ['ru', 'en'], true) ? $locale : 'ru',
                // Public catalog never exposes other users' categories.
                systemOnly: true,
            )
        );

        $data = array_map(fn ($c) => CategoryResource::make($c)->resolve($request), $result);

        return CatalogResponseResource::make($data)->response();
    }

    /**
     * Own categories of the authenticated user. Needs no learning
     * profile, unlike the progress-enriched listing.
     */
    public function mine(Request $request): JsonResponse
    {
        $userId = (string) $request->attributes->get('auth_user_id');
        $locale = (string) $request->query('locale', 'ru');

        $result = $this->useCase->handle(
            new ListCategoriesCommand(
                type: $request->query('type'),
                locale: in_array($locale, ['ru', 'en'], true) ? $locale : 'ru',
                ownerId: $userId,
            )
        );

        $mine = array_values(array_filter(
            $result,
            fn ($c) => $c->userId === $userId,
        ));

        $data = array_map(fn ($c) => CategoryResource::make($c)->resolve($request), $mine);

        return CatalogResponseResource::make($data)->response();
    }
}
