<?php

namespace App\Modules\Library\Application\UseCases\ListShares;

use App\Modules\Library\Domain\Entities\LibraryShare;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class ListSharesUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    /**
     * @return list<LibraryShare>
     */
    public function handle(ListSharesCommand $command): array
    {
        return $this->library->sharesOfCategory($command->userId, $command->categoryId);
    }
}
