<?php

namespace App\Modules\Library\Infrastructure\Persistence\Eloquent;

use App\Modules\Library\Domain\Entities\UserEntry;
use App\Modules\Library\Domain\Entities\UserEntryTranslation;
use App\Modules\Library\Domain\Entities\UserPhrase;
use App\Modules\Library\Domain\Entities\UserPhraseTranslation;
use App\Modules\Library\Domain\Ports\LibraryRepositoryInterface;
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
                ]);
            }

            return $this->toEntry($model);
        });
    }

    public function listEntries(string $userId, ?string $languageId = null): array
    {
        $models = UserEntryModel::query()
            ->where('user_id', $userId)
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
                ]);
            }

            return $this->toPhrase($model);
        });
    }

    public function listPhrases(string $userId, ?string $languageId = null): array
    {
        $models = UserPhraseModel::query()
            ->where('user_id', $userId)
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

    private function toEntry(UserEntryModel $model, ?string $languageId = null): UserEntry
    {
        $q = UserEntryTranslationModel::query()->where('user_entry_id', $model->id);

        if ($languageId !== null) {
            $q->where('language_id', $languageId);
        }

        $translations = $q->get()->map(fn (UserEntryTranslationModel $t) => new UserEntryTranslation(
            (string) $t->id, (string) $t->language_id, $t->text,
            $t->transcription, $t->part_of_speech, $t->notes,
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
            $t->transcription, $t->notes,
        ))->all();

        return new UserPhrase(
            (string) $model->id, (string) $model->user_id,
            $model->category_id ? (string) $model->category_id : null,
            $model->phrase_type, $translations,
        );
    }
}
