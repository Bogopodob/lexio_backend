<?php

namespace App\Modules\User\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class FriendResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
