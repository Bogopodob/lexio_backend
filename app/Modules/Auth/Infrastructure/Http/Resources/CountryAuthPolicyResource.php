<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Domain\Entities\CountryAuthPolicy;
use Illuminate\Http\Resources\Json\JsonResource;

final class CountryAuthPolicyResource extends JsonResource
{
    public function __construct(private readonly CountryAuthPolicy $policy)
    {
        parent::__construct($policy);
    }

    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => [
                'country_iso' => $this->policy->countryIso,
            ],
        ];
    }
}
