<?php

namespace App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Traits;

use App\Shared\Core\Domain\Enum\LanguageEnum;
use App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Models\Translation;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * Relations
 *
 * @property-read Collection<array-key, Translation> translations
 * @property-read Translation translation
 */
trait HasTranslations
{
    abstract protected function translationEntity(): string;

    abstract protected function translationField(): string;

    abstract protected function translationFields(): array;

    /**
     * @param  LanguageEnum[]|null  $fields
     * @param  array<int, string>  $lngs
     */
    public function translations(?array $fields = null, ?array $lngs = null): HasMany
    {
        $query = $this
            ->hasMany(Translation::class, 'entity_id', 'id')
            ->where('entity_type', $this->translationEntity());

        $fields = $fields ?? $this->translationFields();
        $query->whereIn('field', $fields);

        if ($lngs) {
            $query->whereIn(
                'locale',
                array_map(fn (LanguageEnum $lng) => mb_strtolower($lng->name), $lngs)
            );
        }

        return $query;
    }

    public function translation(?LanguageEnum $lgn = null): HasOne
    {
        /** @var string $lgnCode */
        $lgnCode = mb_strtolower($lgn?->name ?: app()->getLocale());

        return $this
            ->hasOne(Translation::class, 'entity_id', 'id')
            ->where('entity_type', $this->translationEntity())
            ->where('field', $this->translationField())
            ->where('locale', $lgnCode);
    }
}
