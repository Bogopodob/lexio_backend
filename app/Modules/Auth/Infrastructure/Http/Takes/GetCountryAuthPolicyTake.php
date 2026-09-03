<?php

namespace App\Modules\Auth\Infrastructure\Http\Takes;

use App\Modules\Auth\Application\UseCases\Auth\GetCountryAuthPolicy\GetCountryAuthPolicyUseCase;
use App\Modules\Auth\Infrastructure\Http\Resources\CountryAuthPolicyResource;
use Illuminate\Http\JsonResponse;

final readonly class GetCountryAuthPolicyTake
{
    public function __construct(
        private GetCountryAuthPolicyUseCase $useCase,
    ) {}

    public function handle(string $countryIso): JsonResponse
    {
        $policy = $this->useCase->handle($countryIso);

        return CountryAuthPolicyResource::make($policy)->response();
    }
}
