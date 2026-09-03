<?php

namespace App\Modules\Auth\Infrastructure\Services;

use App\Modules\Auth\Domain\Entities\CountryAuthPolicy;
use App\Modules\Auth\Domain\Ports\CountryAuthPolicyResolverInterface;

final readonly class ConfigCountryAuthPolicyResolver implements CountryAuthPolicyResolverInterface
{
    public function resolve(string $countryIso): CountryAuthPolicy
    {
        $iso = strtoupper(trim($countryIso));

        return new CountryAuthPolicy(
            countryIso: $iso,
        );
    }
}
