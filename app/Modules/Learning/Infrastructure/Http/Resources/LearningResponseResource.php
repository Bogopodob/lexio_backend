<?php

namespace App\Modules\Learning\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class LearningResponseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'success' => true,
            'data' => $this->resource,
        ];
    }
}
