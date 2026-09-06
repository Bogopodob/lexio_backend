<?php

namespace App\Modules\Library\Infrastructure\Persistence\Eloquent;

use App\Modules\Library\Domain\Entities\LibraryMedia;
use App\Modules\Library\Domain\Entities\LibraryShare;
use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserEntryTranslation;
use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Entities\UserPhraseTranslation;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\Models\LibraryMedia as LibraryMediaModel;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\Models\UserEntity as UserEntryModel;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\Models\UserEntityTranslation as UserEntryTranslationModel;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\Models\UserPhrase as UserPhraseModel;
use App\Modules\Library\Infrastructure\Persistence\Eloquent\Models\UserPhraseTranslation as UserPhraseTranslationModel;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class EloquentLibraryRepository implements LibraryRepositoryInterface
{
    public function saveEntry(UserEntry $entry): UserEntry
    {
        return DB::transaction(function () use ($entry) {
            $model = UserEntryModel::query()->updateOrCreate(
                ['id' => $entry->id !== '' ? $entry->id : Uuid::uuid4()->toString()],
                [
                    'user_id' => $entry->userId,
                    'category_id' => $entry->categoryId,
                    'image_path' => $entry->imagePath,
                ],
            );

            UserEntryTranslationModel::query()->where('user_entry_id', $model->id)->delete();

            foreach ($entry->translations as $t) {
                UserEntryTranslationModel::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'user_entry_id' => $model->id,
                    'language_id' => $t->languageId,
                    'text' => trim($t->text),
                    'transcription' => $t->transcription,
                    'part_of_speech' => $t->partOfSpeech,
                    'notes' => $t->notes,
                    'audio_path' => $t->audioPath,
                ]);
            }

            return $this->toEntry($model);
        });
    }

    /**
     * Own rows plus rows in categories shared with the user.
     */
    private function visibleTo(string $table, string $userId): \Closure
    {
        return function ($query) use ($table, $userId) {
            $query->where(function ($q) use ($table, $userId) {
                $q->where("{$table}.user_id", $userId)
                    ->orWhereExists(function ($sq) use ($table, $userId) {
                        $sq->select(DB::raw(1))
                            ->from('library_shares')
                            ->whereColumn('library_shares.category_id', "{$table}.category_id")
                            ->where('library_shares.friend_user_id', $userId)
                            ->whereColumn('library_shares.owner_user_id', "{$table}.user_id")
                            ->whereNotNull("{$table}.category_id");
                    });
            });
        };
    }

    public function canAccessCategory(string $userId, string $categoryId): bool
    {
        $owner = DB::table('categories')->where('id', $categoryId)->value('user_id');

        if ($owner === null) {
            return true;
        }

        if ((string) $owner === $userId) {
            return true;
        }

        return DB::table('library_shares')
            ->where('category_id', $categoryId)
            ->where('owner_user_id', $owner)
            ->where('friend_user_id', $userId)
            ->exists();
    }

    public function shareCategory(string $ownerId, string $friendId, string $categoryId): LibraryShare
    {
        $row = DB::table('library_shares')->where([
            'owner_user_id' => $ownerId,
            'friend_user_id' => $friendId,
            'category_id' => $categoryId,
        ])->first();

        if (! $row) {
            $id = (string) Uuid::uuid4();

            DB::table('library_shares')->insert([
                'id' => $id,
                'owner_user_id' => $ownerId,
                'friend_user_id' => $friendId,
                'category_id' => $categoryId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $row = DB::table('library_shares')->where('id', $id)->first();
        }

        return new LibraryShare(
            (string) $row->id,
            (string) $row->owner_user_id,
            (string) $row->friend_user_id,
            (string) $row->category_id,
            $this->userName($friendId),
        );
    }

    /**
     * @return list<LibraryShare>
     */
    public function sharesOfCategory(string $ownerId, string $categoryId): array
    {
        return DB::table('library_shares')
            ->where('owner_user_id', $ownerId)
            ->where('category_id', $categoryId)
            ->orderBy('created_at')
            ->get()
            ->map(fn ($row) => new LibraryShare(
                (string) $row->id,
                (string) $row->owner_user_id,
                (string) $row->friend_user_id,
                (string) $row->category_id,
                $this->userName((string) $row->friend_user_id),
            ))
            ->all();
    }

    public function revokeShare(string $ownerId, string $shareId): bool
    {
        return (bool) DB::table('library_shares')
            ->where('id', $shareId)
            ->where('owner_user_id', $ownerId)
            ->delete();
    }

    public function sharedWithMe(string $userId, string $locale): array
    {
        $rows = DB::table('library_shares')
            ->join('categories', 'categories.id', '=', 'library_shares.category_id')
            ->where('library_shares.friend_user_id', $userId)
            ->orderBy('library_shares.created_at', 'desc')
            ->get([
                'categories.id as category_id',
                'categories.slug as slug',
                'library_shares.owner_user_id as owner_id',
            ]);

        $out = [];

        foreach ($rows as $row) {
            $name = DB::table('translations')
                ->where('entity_id', $row->category_id)
                ->where('field', 'name')
                ->where('locale', $locale)
                ->value('value')
                ?? DB::table('translations')
                    ->where('entity_id', $row->category_id)
                    ->where('field', 'name')
                    ->value('value');

            $words = DB::table('user_entries')->where('category_id', $row->category_id)->count()
                + DB::table('user_phrases')->where('category_id', $row->category_id)->count();

            $out[] = [
                'id' => (string) $row->category_id,
                'name' => $name !== null ? (string) $name : (string) $row->slug,
                'owner_name' => $this->userName((string) $row->owner_id),
                'words_count' => $words,
            ];
        }

        return $out;
    }

    public function sharedOwner(string $categoryId, string $friendId): ?string
    {
        $owner = DB::table('categories')->where('id', $categoryId)->value('user_id');

        if ($owner === null) {
            return null;
        }

        if ((string) $owner === $friendId) {
            return $friendId;
        }

        $shared = DB::table('library_shares')
            ->where('category_id', $categoryId)
            ->where('owner_user_id', $owner)
            ->where('friend_user_id', $friendId)
            ->exists();

        return $shared ? (string) $owner : null;
    }

    private function userName(string $userId): ?string
    {
        $name = DB::table('users')->where('id', $userId)->value('name');

        return $name !== null && trim((string) $name) !== '' ? (string) $name : null;
    }

    public function ownsEntry(string $userId, string $entryId): bool
    {
        return UserEntryModel::query()
            ->where('id', $entryId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function ownsPhrase(string $userId, string $phraseId): bool
    {
        return UserPhraseModel::query()
            ->where('id', $phraseId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function getEntry(string $userId, string $entryId): ?UserEntry
    {
        $model = UserEntryModel::query()
            ->where('id', $entryId)
            ->where($this->visibleTo('user_entries', $userId))
            ->first();

        return $model ? $this->toEntry($model) : null;
    }

    public function deleteEntry(string $userId, string $entryId): bool
    {
        return (bool) UserEntryModel::query()
            ->where('id', $entryId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function listEntries(string $userId, ?string $languageId = null, ?string $categoryId = null): array
    {
        $models = UserEntryModel::query()
            ->when(
                $categoryId !== null,
                fn ($q) => $q->where($this->visibleTo('user_entries', $userId))->where('category_id', $categoryId),
                fn ($q) => $q->where('user_id', $userId),
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($models as $model) {
            $entry = $this->toEntry($model, $languageId);

            if ($languageId !== null && $entry->translations === []) {
                continue;
            }

            $result[] = $entry;
        }

        return $result;
    }

    public function savePhrase(UserPhrase $phrase): UserPhrase
    {
        return DB::transaction(function () use ($phrase) {
            $model = UserPhraseModel::query()->updateOrCreate(
                ['id' => $phrase->id !== '' ? $phrase->id : Uuid::uuid4()->toString()],
                [
                    'user_id' => $phrase->userId,
                    'category_id' => $phrase->categoryId,
                    'image_path' => $phrase->imagePath,
                    'phrase_type' => $phrase->phraseType,
                ],
            );

            UserPhraseTranslationModel::query()->where('user_phrase_id', $model->id)->delete();

            foreach ($phrase->translations as $t) {
                UserPhraseTranslationModel::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'user_phrase_id' => $model->id,
                    'language_id' => $t->languageId,
                    'text' => trim($t->text),
                    'transcription' => $t->transcription,
                    'notes' => $t->notes,
                    'audio_path' => $t->audioPath,
                ]);
            }

            return $this->toPhrase($model);
        });
    }

    public function getPhrase(string $userId, string $phraseId): ?UserPhrase
    {
        $model = UserPhraseModel::query()
            ->where('id', $phraseId)
            ->where($this->visibleTo('user_phrases', $userId))
            ->first();

        return $model ? $this->toPhrase($model) : null;
    }

    public function deletePhrase(string $userId, string $phraseId): bool
    {
        return (bool) UserPhraseModel::query()
            ->where('id', $phraseId)
            ->where('user_id', $userId)
            ->delete();
    }

    public function listPhrases(string $userId, ?string $languageId = null, ?string $categoryId = null): array
    {
        $models = UserPhraseModel::query()
            ->when(
                $categoryId !== null,
                fn ($q) => $q->where($this->visibleTo('user_phrases', $userId))->where('category_id', $categoryId),
                fn ($q) => $q->where('user_id', $userId),
            )
            ->orderBy('created_at', 'desc')
            ->get();

        $result = [];

        foreach ($models as $model) {
            $phrase = $this->toPhrase($model, $languageId);

            if ($languageId !== null && $phrase->translations === []) {
                continue;
            }

            $result[] = $phrase;
        }

        return $result;
    }

    public function saveMedia(string $userId, string $kind, string $path, string $mime, int $bytes): LibraryMedia
    {
        $model = LibraryMediaModel::query()->firstOrNew(['path' => $path]);
        $model->user_id = $userId;
        $model->kind = $kind;
        $model->mime = $mime;
        $model->bytes = $bytes;
        $model->save();

        return $this->toMedia($model);
    }

    public function findMedia(string $userId, string $mediaId): ?LibraryMedia
    {
        $model = LibraryMediaModel::query()->where('id', $mediaId)->first();

        if (! $model) {
            return null;
        }

        if ((string) $model->user_id === $userId) {
            return $this->toMedia($model);
        }

        // Shared media: attached to a word in a category shared with the user.
        $needle = '/media/'.(string) $model->id;

        $shared = DB::table('user_entry_translations')
            ->join('user_entries', 'user_entries.id', '=', 'user_entry_translations.user_entry_id')
            ->join('library_shares', 'library_shares.category_id', '=', 'user_entries.category_id')
            ->where('user_entry_translations.audio_path', 'like', '%'.$needle)
            ->where('library_shares.owner_user_id', (string) $model->user_id)
            ->where('library_shares.friend_user_id', $userId)
            ->exists();

        if (! $shared) {
            $shared = DB::table('user_entries')
                ->join('library_shares', 'library_shares.category_id', '=', 'user_entries.category_id')
                ->where('user_entries.image_path', 'like', '%'.$needle)
                ->where('library_shares.owner_user_id', (string) $model->user_id)
                ->where('library_shares.friend_user_id', $userId)
                ->exists();
        }

        if (! $shared) {
            $shared = DB::table('user_phrase_translations')
                ->join('user_phrases', 'user_phrases.id', '=', 'user_phrase_translations.user_phrase_id')
                ->join('library_shares', 'library_shares.category_id', '=', 'user_phrases.category_id')
                ->where('user_phrase_translations.audio_path', 'like', '%'.$needle)
                ->where('library_shares.owner_user_id', (string) $model->user_id)
                ->where('library_shares.friend_user_id', $userId)
                ->exists();
        }

        if (! $shared) {
            $shared = DB::table('user_phrases')
                ->join('library_shares', 'library_shares.category_id', '=', 'user_phrases.category_id')
                ->where('user_phrases.image_path', 'like', '%'.$needle)
                ->where('library_shares.owner_user_id', (string) $model->user_id)
                ->where('library_shares.friend_user_id', $userId)
                ->exists();
        }

        return $shared ? $this->toMedia($model) : null;
    }

    public function findMediaByPath(string $path): ?LibraryMedia
    {
        $model = LibraryMediaModel::query()->where('path', $path)->first();

        return $model ? $this->toMedia($model) : null;
    }

    private function toMedia(LibraryMediaModel $model): LibraryMedia
    {
        return new LibraryMedia(
            (string) $model->id,
            (string) $model->user_id,
            $model->kind,
            $model->path,
            $model->mime,
            (int) $model->bytes,
        );
    }

    private function toEntry(UserEntryModel $model, ?string $languageId = null): UserEntry
    {
        $q = UserEntryTranslationModel::query()->where('user_entry_id', $model->id);

        if ($languageId !== null) {
            $q->where('language_id', $languageId);
        }

        $translations = $q->get()->map(fn (UserEntryTranslationModel $t) => new UserEntryTranslation(
            (string) $t->id, (string) $t->language_id, $t->text,
            $t->transcription, $t->part_of_speech, $t->notes, $t->audio_path,
        ))->all();

        return new UserEntry(
            (string) $model->id, (string) $model->user_id,
            $model->category_id ? (string) $model->category_id : null,
            $model->image_path, $translations,
        );
    }

    private function toPhrase(UserPhraseModel $model, ?string $languageId = null): UserPhrase
    {
        $q = UserPhraseTranslationModel::query()->where('user_phrase_id', $model->id);

        if ($languageId !== null) {
            $q->where('language_id', $languageId);
        }

        $translations = $q->get()->map(fn (UserPhraseTranslationModel $t) => new UserPhraseTranslation(
            (string) $t->id, (string) $t->language_id, $t->text,
            $t->transcription, $t->notes, $t->audio_path,
        ))->all();

        return new UserPhrase(
            (string) $model->id, (string) $model->user_id,
            $model->category_id ? (string) $model->category_id : null,
            $model->phrase_type, $translations, $model->image_path,
        );
    }
}
