<?php

namespace App\Modules\Catalog\Application\UseCases\SaveCategory;

use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use Illuminate\Support\Str;

final readonly class SaveCategoryUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalog,
    ) {}

    /**
     * Creates an own category or updates it. System and foreign
     * categories are untouchable (null = forbidden).
     */
    public function handle(SaveCategoryCommand $command): ?Category
    {
        $name = trim($command->name);

        if ($name === '') {
            return null;
        }

        if ($command->categoryId !== null) {
            $patch = ['name' => mb_substr($name, 0, 60), 'locale' => $command->locale];

            if ($command->color !== null) {
                $patch['color'] = $command->color;
            }

            if ($command->icon !== null) {
                $patch['icon'] = $command->icon;
            }

            if ($command->parentId !== null) {
                $parent = $this->catalog->findCategory($command->parentId);

                if (! $parent || $parent->id === $command->categoryId) {
                    return null;
                }

                $patch['parent_id'] = $parent->id;
            }

            return $this->catalog->updateUserCategory($command->categoryId, $command->userId, $patch);
        }

        $parentId = null;

        if ($command->parentId !== null) {
            $parent = $this->catalog->findCategory($command->parentId);

            if (! $parent) {
                return null;
            }

            $parentId = $parent->id;
        }

        return $this->catalog->createUserCategory(
            userId: $command->userId,
            slug: $this->uniqueSlug($command->userId, $name),
            type: 'theme',
            parentId: $parentId,
            color: $command->color,
            icon: $command->icon,
            name: mb_substr($name, 0, 60),
            locale: $command->locale,
        );
    }

    private function uniqueSlug(string $userId, string $name): string
    {
        $base = trim((string) Str::slug($name));

        if ($base === '') {
            $base = 'cat';
        }

        $slug = mb_substr($base, 0, 40);

        for ($i = 0; $i < 10; $i++) {
            $candidate = $i === 0 ? $slug : $slug.'-'.Str::lower(Str::random(4));

            $exists = $this->catalog->findCategoryBySlug($candidate, $userId);

            if ($exists === null) {
                return $candidate;
            }
        }

        return $slug.'-'.Str::lower(Str::random(8));
    }
}
