<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Application\DTO\VerifiedAccessTokenResult;
use Illuminate\Http\Resources\Json\JsonResource;

final class VerifiedAccessTokenResponseResource extends JsonResource
{
    public function __construct(private readonly VerifiedAccessTokenResult $result)
    {
        parent::__construct($result);
    }

    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => VerifiedAccessTokenResource::make($this->result)->resolve($request),
        ];
    }
}
