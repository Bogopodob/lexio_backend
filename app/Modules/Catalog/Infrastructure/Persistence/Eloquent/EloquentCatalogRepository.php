<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Eloquent;

use App\Modules\Catalog\Domain\Entities\CatalogEntry;
use App\Modules\Catalog\Domain\Entities\CatalogLanguage;
use App\Modules\Catalog\Domain\Entities\CatalogPhrase;
use App\Modules\Catalog\Domain\Entities\Category;
use App\Modules\Catalog\Domain\Entities\EntryDetails;
use App\Modules\Catalog\Domain\Entities\EntryMeaning;
use App\Modules\Catalog\Domain\Entities\EntryMedia;
use App\Modules\Catalog\Domain\Entities\EntrySearchHit;
use App\Modules\Catalog\Domain\Entities\EntryTranslation;
use App\Modules\Catalog\Domain\Entities\PhraseTranslation;
use App\Modules\Catalog\Domain\Entities\PhraseWithTranslations;
use App\Modules\Catalog\Domain\Entities\WordForm;
use App\Modules\Catalog\Domain\Ports\CatalogRepositoryInterface;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category as CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry as EntryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning as EntryMeaningModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMedia as EntryMediaModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation as EntryTranslationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language as LanguageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Phrase as PhraseModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\PhraseTranslation as PhraseTranslationModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\WordForm as WordFormModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class EloquentCatalogRepository implements CatalogRepositoryInterface
{
    public function listLanguages(bool $onlyActive = true): array
    {
        $query = LanguageModel::query()->orderBy('sort');

        if ($onlyActive) {
            $query->where('is_active', true);
        }

        return $query->get()->map(fn (LanguageModel $m) => new CatalogLanguage(
            id: (string) $m->id,
            code: $m->code,
            name: $m->name,
            nativeName: $m->native_name,
            direction: $m->direction,
            isActive: (bool) $m->is_active,
            sort: (int) $m->sort,
        ))->all();
    }

    public function listCategories(?string $type = null, string $locale = 'ru'): array
    {
        $query = CategoryModel::query()->orderBy('sort');

        if ($type !== null) {
            $query->where('type', $type);
        }

        $models = $query->get();

        if ($models->isEmpty()) {
            return [];
        }

        $ids = $models->pluck('id')->map(fn ($id) => (string) $id)->all();

        $names = DB::table('translations')
            ->whereIn('entity_type', [
                CategoryModel::class,
                'App\\Modules\\Catalog\\Infrastructure\\Persistence\\Eloquent\\Category',
            ])
            ->whereIn('entity_id', $ids)
            ->where('field', 'name')
            ->where('locale', $locale)
            ->pluck('value', 'entity_id');

        $counts = DB::table('entry_category')
            ->selectRaw('category_id, COUNT(*) as total')
            ->whereIn('category_id', $ids)
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $models->map(fn (CategoryModel $m) => new Category(
            id: (string) $m->id,
            parentId: $m->parent_id ? (string) $m->parent_id : null,
            userId: $m->user_id ? (string) $m->user_id : null,
            slug: $m->slug,
            isSystem: (bool) $m->is_system,
            type: $m->type,
            color: $m->color,
            icon: $m->icon,
            sort: (int) $m->sort,
            name: $names->get((string) $m->id),
            entriesCount: (int) ($counts->get((string) $m->id) ?? 0),
        ))->all();
    }

    public function searchEntries(string $languageId, string $query, ?string $level = null, int $limit = 20): array
    {
        $rows = EntryTranslationModel::query()
            ->join('entries', 'entries.id', '=', 'entry_translations.entry_id')
            ->where('entry_translations.language_id', $languageId)
            ->where('entry_translations.text', 'like', '%'.$query.'%')
            ->when($level !== null, fn ($q) => $q->where('entries.level', $level))
            ->orderBy('entries.frequency_rank')
            ->limit($limit)
            ->get(['entries.id as entry_id', 'entries.level', 'entries.image_path', 'entry_translations.text']);

        return $rows->map(fn ($r) => new EntrySearchHit(
            entryId: (string) $r->entry_id,
            level: $r->level,
            imagePath: $r->image_path,
            matchedText: $r->text,
            languageId: $languageId,
        ))->all();
    }

    public function pickDailyEntryId(string $date): ?string
    {
        try {
            $dayOfYear = Carbon::parse($date)->dayOfYear;
        } catch (\Throwable) {
            return null;
        }

        $count = EntryModel::query()->count();

        if ($count === 0) {
            return null;
        }

        $id = EntryModel::query()
            ->orderBy('id')
            ->offset($dayOfYear % $count)
            ->value('id');

        return $id ? (string) $id : null;
    }

    public function getEntryDetails(string $entryId): ?EntryDetails
    {
        $entry = EntryModel::query()->find($entryId);

        if (! $entry) {
            return null;
        }

        $meanings = EntryMeaningModel::query()->where('entry_id', $entryId)->get()->map(
            fn (EntryMeaningModel $m) => new EntryMeaning((string) $m->id, (string) $m->entry_id, $m->note)
        )->all();

        $translations = EntryTranslationModel::query()->where('entry_id', $entryId)->get()->map(
            fn (EntryTranslationModel $m) => new EntryTranslation(
                (string) $m->id, (string) $m->entry_id, (string) $m->meaning_id,
                (string) $m->language_id, $m->text, $m->transcription, $m->audio_path, $m->part_of_speech,
            )
        )->all();

        $translationIds = array_map(fn (EntryTranslation $t) => $t->id, $translations);

        $forms = empty($translationIds) ? [] : WordFormModel::query()->whereIn('entry_translation_id', $translationIds)->get()->map(
            fn (WordFormModel $m) => new WordForm((string) $m->id, (string) $m->entry_translation_id, $m->form, $m->form_type)
        )->all();

        $links = DB::table('entry_phrase')->where('entry_id', $entryId)->get();
        $examples = [];

        foreach ($links as $link) {
            $phrase = PhraseModel::query()->find($link->phrase_id);

            if (! $phrase) {
                continue;
            }

            $phraseTranslations = PhraseTranslationModel::query()->where('phrase_id', $phrase->id)->get()->map(
                fn (PhraseTranslationModel $m) => new PhraseTranslation(
                    (string) $m->id, (string) $m->phrase_id, (string) $m->language_id,
                    $m->text, $m->transcription, $m->audio_path,
                )
            )->all();

            $examples[] = new PhraseWithTranslations(
                new CatalogPhrase((string) $phrase->id, $phrase->level, $phrase->phrase_type, $phrase->image_path),
                $phraseTranslations,
                $link->meaning_id ? (string) $link->meaning_id : null,
            );
        }

        $media = EntryMediaModel::query()->where('entry_id', $entryId)->orderBy('sort')->get()->map(
            fn (EntryMediaModel $m) => new EntryMedia(
                (string) $m->id,
                $m->entry_id ? (string) $m->entry_id : null,
                $m->entry_translation_id ? (string) $m->entry_translation_id : null,
                $m->type, $m->path, $m->source, $m->source_title,
                $m->content_source_id ? (string) $m->content_source_id : null,
                (int) $m->sort,
            )
        )->all();

        $categoryIds = DB::table('entry_category')
            ->where('entry_id', $entryId)
            ->pluck('category_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return new EntryDetails(
            new CatalogEntry((string) $entry->id, $entry->level, $entry->image_path, $entry->frequency_rank !== null ? (int) $entry->frequency_rank : null),
            $meanings,
            $translations,
            $forms,
            $examples,
            $media,
            $categoryIds,
        );
    }
}
