<?php

namespace App\Modules\Catalog\Application\UseCases\ListCategories;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class ListCategoriesUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalogRepository,
    ) {}

    /**
     * @return list<Category>
     */
    public function handle(ListCategoriesCommand $command): array
    {
        return $this->catalogRepository->listCategories($command->type, $command->locale);
    }
}
