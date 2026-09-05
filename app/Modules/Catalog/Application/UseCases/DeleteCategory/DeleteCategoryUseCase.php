<?php

namespace App\Modules\Catalog\Application\UseCases\DeleteCategory;

use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class DeleteCategoryUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalog,
    ) {}

    /**
     * Deletes an own category. Words stay, pivots are dropped
     * by foreign keys. System and foreign categories: false.
     */
    public function handle(DeleteCategoryCommand $command): bool
    {
        return $this->catalog->deleteUserCategory($command->categoryId, $command->userId);
    }
}
