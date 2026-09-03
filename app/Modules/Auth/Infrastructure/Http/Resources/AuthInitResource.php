<?php

namespace App\Modules\Auth\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class AuthInitResource extends JsonResource
{
    /**
     * @param  mixed  $request
     * @return array{success: bool, data: mixed}
     */
    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
