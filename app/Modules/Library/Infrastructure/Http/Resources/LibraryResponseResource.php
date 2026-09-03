<?php

namespace App\Modules\Library\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class LibraryResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
