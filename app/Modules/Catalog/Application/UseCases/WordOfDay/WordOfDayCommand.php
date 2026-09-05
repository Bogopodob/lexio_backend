<?php

namespace App\Modules\Catalog\Application\UseCases\WordOfDay;

final readonly class WordOfDayCommand
{
    public function __construct(
        public string $date,
    ) {}
}
