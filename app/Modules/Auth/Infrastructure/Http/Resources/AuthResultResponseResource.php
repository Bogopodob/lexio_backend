<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use App\Modules\Auth\Application\DTO\AuthResult;
use Illuminate\Http\Resources\Json\JsonResource;

final class AuthResultResponseResource extends JsonResource
{
    public function __construct(private readonly AuthResult $result)
    {
        parent::__construct($result);
    }

    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => AuthResultResource::make($this->result)->resolve($request),
        ];
    }
}
