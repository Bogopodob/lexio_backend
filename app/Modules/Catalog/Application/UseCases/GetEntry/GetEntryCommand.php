<?php

namespace App\Modules\Catalog\Application\UseCases\GetEntry;

final readonly class GetEntryCommand
{
    public function __construct(
        public string $entryId,
    ) {}
}
