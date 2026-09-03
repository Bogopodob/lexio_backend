<?php

namespace App\Modules\Catalog\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class CatalogResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
