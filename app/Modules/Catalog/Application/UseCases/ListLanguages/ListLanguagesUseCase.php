<?php

namespace App\Modules\Catalog\Application\UseCases\ListLanguages;

use App\Modules\Catalog\Domain\Entities\CatalogLanguage;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class ListLanguagesUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalogRepository,
    ) {}

    /**
     * @return list<CatalogLanguage>
     */
    public function handle(ListLanguagesCommand $command): array
    {
        return $this->catalogRepository->listLanguages($command->onlyActive);
    }
}
