<?php

namespace App\Modules\Auth\Application\UseCases\Auth\GetCountryAuthPolicy;

use App\Modules\Auth\Domain\Entities\CountryAuthPolicy;
use App\Modules\Auth\Domain\Ports\CountryAuthPolicyResolverInterface;

final readonly class GetCountryAuthPolicyUseCase
{
    public function __construct(
        private CountryAuthPolicyResolverInterface $resolver,
    ) {}

    public function handle(string $countryIso): CountryAuthPolicy
    {
        return $this->resolver->resolve($countryIso);
    }
}
