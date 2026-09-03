<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class ProfileWithStat
{
    public function __construct(
        public LanguageProfile $profile,
        public ?LanguageStat $stat,
    ) {}
}
