<?php

namespace App\Modules\Catalog\Domain\Ports;

use App\Modules\Catalog\Domain\Entities\CatalogLanguage;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Entities\EntryDetails;
use App\Modules\Catalog\Domain\Entities\EntrySearchHit;

interface CatalogRepositoryInterface
{
    /**
     * @return list<CatalogLanguage>
     */
    public function listLanguages(bool $onlyActive = true): array;

    /**
     * @return list<Category>
     */
    public function listCategories(?string $type = null): array;

    /**
     * @return list<EntrySearchHit>
     */
    public function searchEntries(string $languageId, string $query, ?string $level = null, int $limit = 20): array;

    public function getEntryDetails(string $entryId): ?EntryDetails;
}
