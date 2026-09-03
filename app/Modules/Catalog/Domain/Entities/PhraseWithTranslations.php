<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class PhraseWithTranslations
{
    /**
     * @param  list<PhraseTranslation>  $translations
     */
    public function __construct(
        public CatalogPhrase $phrase,
        public array $translations,
        public ?string $meaningId,
    ) {}
}
