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
     * System categories (user_id null) plus, when $ownerId is given,
     * the owner's own categories. $systemOnly hides user categories.
     *
     * @return list<Category>
     */
    public function listCategories(
        ?string $type = null,
        string $locale = 'ru',
        ?string $ownerId = null,
        bool $systemOnly = false,
    ): array;

    public function findCategory(string $id): ?Category;

    public function findCategoryBySlug(string $slug, ?string $userId): ?Category;

    public function createUserCategory(
        string $userId,
        string $slug,
        string $type,
        ?string $parentId,
        ?string $color,
        ?string $icon,
        string $name,
        string $locale,
    ): Category;

    public function updateUserCategory(string $id, string $userId, array $patch): ?Category;

    public function deleteUserCategory(string $id, string $userId): bool;

    /**
     * @return list<EntrySearchHit>
     */
    public function searchEntries(string $languageId, string $query, ?string $level = null, int $limit = 20): array;

    public function getEntryDetails(string $entryId): ?EntryDetails;

    /**
     * Deterministic entry id for a calendar date (day of year modulo count).
     */
    public function pickDailyEntryId(string $date): ?string;

    /**
     * Random entry ids having translations in both languages.
     *
     * @return list<string>
     */
    public function randomEntryIds(string $enId, string $ruId, int $count): array;
}
