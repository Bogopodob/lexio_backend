<?php

namespace App\Modules\Catalog\Application\UseCases\ListLanguages;

final readonly class ListLanguagesCommand
{
    public function __construct(
        public bool $onlyActive = true,
    ) {}
}
