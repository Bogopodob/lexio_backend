<?php

namespace App\Modules\Catalog\Infrastructure\Http\Resources;

use App\Modules\Catalog\Domain\Entities\CatalogLanguage;
use Illuminate\Http\Resources\Json\JsonResource;

final class LanguageResource extends JsonResource
{
    public function __construct(private readonly CatalogLanguage $language)
    {
        parent::__construct($language);
    }

    public function toArray($request): array
    {
        return [
            'id' => $this->language->id,
            'code' => $this->language->code,
            'name' => $this->language->name,
            'native_name' => $this->language->nativeName,
            'direction' => $this->language->direction,
            'is_active' => $this->language->isActive,
            'sort' => $this->language->sort,
        ];
    }
}
