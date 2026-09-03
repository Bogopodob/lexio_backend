<?php

namespace App\Modules\Catalog\Application\UseCases\SearchEntries;

use App\Modules\Catalog\Domain\Entities\EntrySearchHit;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class SearchEntriesUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalogRepository,
    ) {}

    /**
     * @return list<EntrySearchHit>
     */
    public function handle(SearchEntriesCommand $command): array
    {
        return $this->catalogRepository->searchEntries(
            $command->languageId,
            $command->query,
            $command->level,
            $command->limit,
        );
    }
}
