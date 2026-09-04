<?php

namespace App\Modules\Learning\Application\UseCases\ListCategoriesWithProgress;

use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;

final readonly class ListCategoriesWithProgressUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalog,
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
    ) {}

    /**
     * Catalog categories enriched with the profile's learned counts.
     *
     * @return list<array{id: string, parent_id: ?string, slug: string, type: string, color: ?string, icon: ?string, sort: int, name: ?string, entries_count: int, learned_count: int}>|null
     */
    public function handle(ListCategoriesWithProgressCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $learned = $this->progress->countLearnedByCategory($command->profileId);
        $result = [];

        foreach ($this->catalog->listCategories($command->type, $command->locale) as $category) {
            $result[] = [
                'id' => $category->id,
                'parent_id' => $category->parentId,
                'slug' => $category->slug,
                'type' => $category->type,
                'color' => $category->color,
                'icon' => $category->icon,
                'sort' => $category->sort,
                'name' => $category->name,
                'entries_count' => $category->entriesCount,
                'learned_count' => $learned[$category->id] ?? 0,
            ];
        }

        return $result;
    }
}
