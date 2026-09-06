<?php

namespace App\Modules\Library\Application\UseCases\RevokeShare;

use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;

final readonly class RevokeShareUseCase
{
    public function __construct(
        private LibraryRepositoryInterface $library,
    ) {}

    public function handle(RevokeShareCommand $command): bool
    {
        return $this->library->revokeShare($command->userId, $command->shareId);
    }
}
