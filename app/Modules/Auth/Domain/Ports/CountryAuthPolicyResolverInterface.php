<?php

namespace App\Modules\Auth\Domain\Ports;

use App\Modules\Auth\Domain\Entities\CountryAuthPolicy;

interface CountryAuthPolicyResolverInterface
{
    public function resolve(string $countryIso): CountryAuthPolicy;
}
