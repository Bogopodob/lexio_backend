<?php

namespace App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Category;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\ContentSource;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMedia;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Phrase;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\PhraseTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\WordForm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $ru = Language::query()->where('code', 'ru')->first();
        $en = Language::query()->where('code', 'en')->first();
        $es = Language::query()->where('code', 'es')->first();

        if (! $ru || ! $en) {
            return;
        }

        $already = EntryTranslation::query()
            ->where('language_id', $ru->id)
            ->where('text', 'мир')
            ->exists();

        if ($already) {
            return;
        }

        DB::transaction(function () use ($ru, $en, $es) {
            $entry = Entry::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'level' => 'A2',
            ]);

            $peace = EntryMeaning::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_id' => $entry->id,
                'note' => 'peace (покой, отсутствие войны)',
            ]);

            $world = EntryMeaning::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_id' => $entry->id,
                'note' => 'world (вселенная, земной шар)',
            ]);

            $rows = [
                [$peace->id, $ru->id, 'мир', null, 'noun'],
                [$peace->id, $en->id, 'peace', 'piːs', 'noun'],
                [$world->id, $ru->id, 'мир', null, 'noun'],
                [$world->id, $en->id, 'world', 'wɜːld', 'noun'],
            ];

            if ($es) {
                $rows[] = [$peace->id, $es->id, 'paz', null, 'noun'];
                $rows[] = [$world->id, $es->id, 'mundo', null, 'noun'];
            }

            $byKey = [];

            foreach ($rows as [$meaningId, $languageId, $text, $transcription, $pos]) {
                $tr = EntryTranslation::query()->create([
                    'id' => Uuid::uuid4()->toString(),
                    'entry_id' => $entry->id,
                    'meaning_id' => $meaningId,
                    'language_id' => $languageId,
                    'text' => $text,
                    'transcription' => $transcription,
                    'part_of_speech' => $pos,
                ]);

                $byKey[$meaningId.':'.$languageId] = $tr->id;
            }

            WordForm::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_translation_id' => $byKey[$peace->id.':'.$en->id],
                'form' => 'peaceful',
                'form_type' => 'adjective',
            ]);

            WordForm::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_translation_id' => $byKey[$world->id.':'.$en->id],
                'form' => 'worlds',
                'form_type' => 'plural noun',
            ]);

            $phrase = Phrase::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'level' => 'A2',
                'phrase_type' => 'example',
            ]);

            PhraseTranslation::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'phrase_id' => $phrase->id,
                'language_id' => $ru->id,
                'text' => 'Мир во всём мире',
            ]);

            PhraseTranslation::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'phrase_id' => $phrase->id,
                'language_id' => $en->id,
                'text' => 'World peace',
            ]);

            DB::table('entry_phrase')->insert([
                'entry_id' => $entry->id,
                'phrase_id' => $phrase->id,
                'meaning_id' => null,
            ]);

            EntryMedia::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_id' => $entry->id,
                'entry_translation_id' => null,
                'type' => 'image',
                'path' => 'demo/mir.jpg',
                'source' => 'own',
                'source_title' => null,
            ]);

            EntryMedia::query()->create([
                'id' => Uuid::uuid4()->toString(),
                'entry_id' => null,
                'entry_translation_id' => $byKey[$peace->id.':'.$en->id],
                'type' => 'audio',
                'path' => 'demo/peace.mp3',
                'source' => 'own',
                'source_title' => null,
            ]);

            $movie = ContentSource::query()->firstOrCreate(
                ['type' => 'movie', 'title' => 'Inception'],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'language_id' => $en->id,
                    'level' => 'B2',
                ],
            );

            EntryMedia::query()->firstOrCreate(
                [
                    'entry_translation_id' => $byKey[$world->id.':'.$en->id],
                    'type' => 'video',
                ],
                [
                    'id' => Uuid::uuid4()->toString(),
                    'entry_id' => null,
                    'path' => 'demo/inception-world.mp4',
                    'source' => 'movie',
                    'source_title' => 'Inception',
                    'content_source_id' => $movie->id,
                ],
            );

            $category = Category::query()->where('slug', 'nature_world')->first();

            if ($category) {
                DB::table('entry_category')->insert([
                    'entry_id' => $entry->id,
                    'category_id' => $category->id,
                ]);
            }
        });
    }
}
