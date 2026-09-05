<?php

namespace App\Modules\Catalog\Application\UseCases\WordOfDay;

use App\Modules\Catalog\Domain\Entities\EntryDetails;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;

final readonly class WordOfDayUseCase
{
    public function __construct(
        private CatalogRepositoryInterface $catalog,
    ) {}

    public function handle(WordOfDayCommand $command): ?EntryDetails
    {
        $entryId = $this->catalog->pickDailyEntryId($command->date);

        if (! $entryId) {
            return null;
        }

        return $this->catalog->getEntryDetails($entryId);
    }
}
