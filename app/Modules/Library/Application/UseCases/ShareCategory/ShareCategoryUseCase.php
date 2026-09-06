<?php

namespace App\Modules\Library\Application\UseCases\ShareCategory;

use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Library\Domain\Entities\LibraryShare;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use App\Modules\User\Domain\Ports\FriendshipRepositoryInterface;

final readonly class ShareCategoryUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
        private CatalogRepositoryInterface $categories,
        private FriendshipRepositoryInterface $friendships,
    ) {}

    /**
     * Null when the category is not own, the friend is invalid,
     * or there is no accepted friendship.
     */
    public function handle(ShareCategoryCommand $command): ?LibraryShare
    {
        if ($command->friendId === $command->userId) {
            return null;
        }

        $category = $this->categories->findCategory($command->categoryId);

        if (! $category || $category->userId !== $command->userId) {
            return null;
        }

        if (! $this->friendships->areFriends($command->userId, $command->friendId)) {
            return null;
        }

        return $this->library->shareCategory($command->userId, $command->friendId, $command->categoryId);
    }
}
