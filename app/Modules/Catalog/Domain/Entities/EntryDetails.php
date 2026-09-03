<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class EntryDetails
{
    /**
     * @param  list<EntryMeaning>  $meanings
     * @param  list<EntryTranslation>  $translations
     * @param  list<WordForm>  $forms
     * @param  list<PhraseWithTranslations>  $examples
     * @param  list<EntryMedia>  $media
     * @param  list<string>  $categoryIds
     */
    public function __construct(
        public CatalogEntry $entry,
        public array $meanings,
        public array $translations,
        public array $forms,
        public array $examples,
        public array $media,
        public array $categoryIds,
    ) {}
}
