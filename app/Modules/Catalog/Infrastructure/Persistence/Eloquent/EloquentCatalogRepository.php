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
use Ramsey\Uuid\Uuid;

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

    public function listCategories(
        ?string $type = null,
        string $locale = 'ru',
        ?string $ownerId = null,
        bool $systemOnly = false,
    ): array {
        $query = CategoryModel::query()->orderBy('sort');

        if ($type !== null) {
            $query->where('type', $type);
        }

        if ($ownerId !== null && ! $systemOnly) {
            $query->where(function ($q) use ($ownerId) {
                $q->whereNull('user_id')->orWhere('user_id', $ownerId);
            });
        } else {
            $query->whereNull('user_id');
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

        $phraseCounts = DB::table('phrases_categories')
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
            phrasesCount: (int) ($phraseCounts->get((string) $m->id) ?? 0),
        ))->all();
    }

    public function findCategory(string $id): ?Category
    {
        $model = CategoryModel::query()->find($id);

        if (! $model) {
            return null;
        }

        return new Category(
            id: (string) $model->id,
            parentId: $model->parent_id ? (string) $model->parent_id : null,
            userId: $model->user_id ? (string) $model->user_id : null,
            slug: $model->slug,
            isSystem: (bool) $model->is_system,
            type: $model->type,
            color: $model->color,
            icon: $model->icon,
            sort: (int) $model->sort,
            name: null,
            entriesCount: 0,
        );
    }

    public function findCategoryBySlug(string $slug, ?string $userId): ?Category
    {
        $model = CategoryModel::query()
            ->where('slug', $slug)
            ->when($userId === null, fn ($q) => $q->whereNull('user_id'), fn ($q) => $q->where('user_id', $userId))
            ->first();

        return $model ? $this->findCategory((string) $model->id) : null;
    }

    public function createUserCategory(
        string $userId,
        string $slug,
        string $type,
        ?string $parentId,
        ?string $color,
        ?string $icon,
        string $name,
        string $locale,
    ): Category {
        $id = (string) Uuid::uuid4();

        CategoryModel::query()->create([
            'id' => $id,
            'parent_id' => $parentId,
            'user_id' => $userId,
            'slug' => $slug,
            'is_system' => false,
            'type' => $type,
            'color' => $color,
            'icon' => $icon,
            'sort' => 500,
        ]);

        DB::table('translations')->insert([
            'id' => (string) Uuid::uuid4(),
            'entity_type' => CategoryModel::class,
            'entity_id' => $id,
            'locale' => $locale,
            'field' => 'name',
            'value' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new Category(
            id: $id,
            parentId: $parentId,
            userId: $userId,
            slug: $slug,
            isSystem: false,
            type: $type,
            color: $color,
            icon: $icon,
            sort: 500,
            name: $name,
            entriesCount: 0,
        );
    }

    public function updateUserCategory(string $id, string $userId, array $patch): ?Category
    {
        $model = CategoryModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (! $model) {
            return null;
        }

        $fields = [];

        foreach (['color', 'icon', 'parent_id'] as $field) {
            if (array_key_exists($field, $patch)) {
                $fields[$field] = $patch[$field];
            }
        }

        if ($fields !== []) {
            $model->update($fields);
        }

        if (isset($patch['name'], $patch['locale'])) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => CategoryModel::class,
                    'entity_id' => $id,
                    'locale' => $patch['locale'],
                    'field' => 'name',
                ],
                ['value' => $patch['name'], 'updated_at' => now()],
            );
        }

        $fresh = $this->findCategory($id);

        if ($fresh && isset($patch['name'])) {
            $fresh = new Category(
                id: $fresh->id,
                parentId: $fresh->parentId,
                userId: $fresh->userId,
                slug: $fresh->slug,
                isSystem: $fresh->isSystem,
                type: $fresh->type,
                color: $fresh->color,
                icon: $fresh->icon,
                sort: $fresh->sort,
                name: $patch['name'],
                entriesCount: $fresh->entriesCount,
            );
        }

        return $fresh;
    }

    public function deleteUserCategory(string $id, string $userId): bool
    {
        return (bool) CategoryModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete();
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

    public function randomEntryIds(string $enId, string $ruId, int $count): array
    {
        return EntryModel::query()
            ->whereExists(function ($q) use ($enId) {
                $q->select(DB::raw(1))
                    ->from('entry_translations as en')
                    ->whereColumn('en.entry_id', 'entries.id')
                    ->where('en.language_id', $enId);
            })
            ->whereExists(function ($q) use ($ruId) {
                $q->select(DB::raw(1))
                    ->from('entry_translations as ru')
                    ->whereColumn('ru.entry_id', 'entries.id')
                    ->where('ru.language_id', $ruId);
            })
            ->inRandomOrder()
            ->limit(max(1, min(10, $count)))
            ->pluck('entries.id')
            ->map(fn ($id) => (string) $id)
            ->all();
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
