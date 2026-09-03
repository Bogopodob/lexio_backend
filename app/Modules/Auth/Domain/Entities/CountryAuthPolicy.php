<?php

namespace App\Modules\Auth\Domain\Entities;

final readonly class CountryAuthPolicy
{
    public function __construct(
        public string $countryIso,
    ) {}
}
