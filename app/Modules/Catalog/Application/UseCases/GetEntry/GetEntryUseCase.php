<?php

namespace App\Modules\Catalog\Application\UseCases\GetEntry;

use App\Modules\Catalog\Domain\Entities\EntryDetails;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class GetEntryUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalogRepository,
    ) {}

    public function handle(GetEntryCommand $command): ?EntryDetails
    {
        return $this->catalogRepository->getEntryDetails($command->entryId);
    }
}
