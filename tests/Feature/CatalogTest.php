<?php

namespace Tests\Feature;

use App\Modules\Catalog\Application\UseCases\GetEntry\GetEntryCommand;
use App\Modules\Catalog\Application\UseCases\GetEntry\GetEntryUseCase;
use App\Modules\Catalog\Application\UseCases\ListLanguages\ListLanguagesCommand;
use App\Modules\Catalog\Application\UseCases\ListLanguages\ListLanguagesUseCase;
use App\Modules\Catalog\Application\UseCases\SearchEntries\SearchEntriesCommand;
use App\Modules\Catalog\Application\UseCases\SearchEntries\SearchEntriesUseCase;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\WordForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private string $ru;

    private string $en;

    private string $entryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ru = Language::query()->create([
            'code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский',
        ])->id;
        $this->en = Language::query()->create([
            'code' => 'en', 'name' => 'English', 'native_name' => 'English',
        ])->id;

        $entry = Entry::query()->create(['level' => 'A2']);
        $this->entryId = $entry->id;

        $peace = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'peace']);
        $world = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'world']);

        foreach ([
            [$peace->id, $this->ru, 'мир'],
            [$peace->id, $this->en, 'peace'],
            [$world->id, $this->ru, 'мир'],
            [$world->id, $this->en, 'world'],
        ] as [$meaningId, $languageId, $text]) {
            EntryTranslation::query()->create([
                'entry_id' => $entry->id,
                'meaning_id' => $meaningId,
                'language_id' => $languageId,
                'text' => $text,
            ]);
        }
    }

    public function test_word_of_day_is_deterministic(): void
    {
        $first = $this->getJson('/api/catalog/word-of-day?date=2026-09-05');

        $first->assertOk()
            ->assertJsonPath('data.date', '2026-09-05')
            ->assertJsonPath('data.word', 'peace')
            ->assertJsonPath('data.translation', 'мир');

        // Same date → same word.
        $this->getJson('/api/catalog/word-of-day?date=2026-09-05')
            ->assertOk()
            ->assertJsonPath('data.word', 'peace');

        $this->getJson('/api/catalog/word-of-day?date=not-a-date')->assertStatus(422);
    }

    public function test_quiz_round_returns_question_and_options(): void
    {
        foreach ([['sun', 'солнце'], ['moon', 'луна'], ['star', 'звезда']] as [$enText, $ruText]) {
            $entry = Entry::query()->create(['level' => 'A1']);
            $meaning = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => $ruText]);
            EntryTranslation::query()->create([
                'entry_id' => $entry->id, 'meaning_id' => $meaning->id,
                'language_id' => $this->en, 'text' => $enText,
            ]);
            EntryTranslation::query()->create([
                'entry_id' => $entry->id, 'meaning_id' => $meaning->id,
                'language_id' => $this->ru, 'text' => $ruText,
            ]);
        }

        $response = $this->getJson('/api/catalog/quiz-round?count=4');

        $response->assertOk()->assertJsonPath('success', true);

        $data = $response->json('data');

        $this->assertNotEmpty($data['question']['word']);
        $this->assertCount(4, $data['options']);
        $this->assertContains($data['options'][$data['correct_index']], ['мир', 'солнце', 'луна', 'звезда']);
        $this->assertCount(4, array_unique($data['options']));
    }

    public function test_error_messages_follow_accept_language(): void
    {
        $missing = '00000000-0000-0000-0000-000000000000';

        // Backend default is Russian (the test client injects
        // en-us when the header is absent, so send it empty).
        $this->getJson("/api/catalog/entries/{$missing}", ['Accept-Language' => ''])
            ->assertNotFound()
            ->assertJsonPath('message', 'Запись не найдена');

        $this->getJson("/api/catalog/entries/{$missing}", ['Accept-Language' => 'en'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Entry not found');

        $this->getJson("/api/catalog/entries/{$missing}", ['Accept-Language' => 'ru-RU,ru;q=0.9,en;q=0.8'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Запись не найдена');

        // Unknown language falls back to the Russian default.
        $this->getJson("/api/catalog/entries/{$missing}", ['Accept-Language' => 'de'])
            ->assertNotFound()
            ->assertJsonPath('message', 'Запись не найдена');
    }

    private function registerUser(string $email): array
    {
        $registered = $this->postJson('/api/register', [
            'email' => $email,
            'password' => 'secret123',
        ])->json('data');

        return ['id' => $registered['user']['id'], 'token' => $registered['token']];
    }

    public function test_user_category_crud_and_visibility(): void
    {
        $anna = $this->registerUser('cat-anna@example.com');
        $boris = $this->registerUser('cat-boris@example.com');

        // Guest cannot create.
        $this->postJson('/api/catalog/categories', ['name' => 'Моё'])
            ->assertUnauthorized();

        $created = $this->withToken($anna['token'])->postJson('/api/catalog/categories', [
            'name' => 'Мои глаголы',
            'icon' => '📚',
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.slug', 'moi-glagoly')
            ->assertJsonPath('data.user_id', $anna['id']);

        $catId = $created->json('data.id');

        // Same name twice → unique slug, both created.
        $again = $this->withToken($anna['token'])->postJson('/api/catalog/categories', [
            'name' => 'Мои глаголы',
        ]);
        $again->assertCreated();
        $this->assertNotSame($catId, $again->json('data.id'));
        $this->assertNotSame('moi-glagoly', $again->json('data.slug'));

        // Boris cannot touch Anna's category.
        $this->withToken($boris['token'])->patchJson("/api/catalog/categories/{$catId}", [
            'name' => 'Чужое',
        ])->assertForbidden();
        $this->withToken($boris['token'])->deleteJson("/api/catalog/categories/{$catId}")
            ->assertForbidden();

        // Anna renames her own.
        $this->withToken($anna['token'])->patchJson("/api/catalog/categories/{$catId}", [
            'name' => 'Мои глаголы 2',
        ])->assertOk()->assertJsonPath('data.name', 'Мои глаголы 2');

        // Public list hides user categories.
        $public = $this->getJson('/api/catalog/categories?type=theme')->json('data');
        $this->assertNotContains($catId, array_column($public, 'id'));

        // Words survive category deletion, pivots do not.
        \Illuminate\Support\Facades\DB::table('entry_category')->insert([
            'entry_id' => $this->entryId,
            'category_id' => $catId,
        ]);

        $this->withToken($anna['token'])->deleteJson("/api/catalog/categories/{$catId}")
            ->assertOk();

        $this->assertNull(
            \Illuminate\Support\Facades\DB::table('categories')->where('id', $catId)->first()
        );
        $this->assertNotNull(
            \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry::query()->find($this->entryId)
        );
        $this->assertSame(
            0,
            \Illuminate\Support\Facades\DB::table('entry_category')->where('category_id', $catId)->count()
        );
    }

    public function test_mine_lists_only_own_categories(): void
    {
        $anna = $this->registerUser('mine-anna@example.com');
        $boris = $this->registerUser('mine-boris@example.com');

        $this->getJson('/api/catalog/categories/mine')->assertUnauthorized();

        $catId = $this->withToken($anna['token'])->postJson('/api/catalog/categories', [
            'name' => 'Моё',
        ])->assertCreated()->json('data.id');

        $mine = $this->withToken($anna['token'])->getJson('/api/catalog/categories/mine');
        $mine->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $catId);

        $theirs = $this->withToken($boris['token'])->getJson('/api/catalog/categories/mine');
        $theirs->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_lists_languages(): void
    {
        $result = app(ListLanguagesUseCase::class)->handle(new ListLanguagesCommand);

        $this->assertCount(2, $result);
        $this->assertSame('ru', $result[0]->code);
    }

    public function test_search_finds_entry_by_translation(): void
    {
        $result = app(SearchEntriesUseCase::class)->handle(
            new SearchEntriesCommand($this->en, 'peace')
        );

        $this->assertCount(1, $result);
        $this->assertSame($this->entryId, $result[0]->entryId);
        $this->assertSame('peace', $result[0]->matchedText);
    }

    public function test_one_language_can_have_several_translations_of_one_entry(): void
    {
        $details = app(GetEntryUseCase::class)->handle(new GetEntryCommand($this->entryId));

        $this->assertNotNull($details);
        $this->assertCount(2, $details->meanings);

        $ruTexts = array_map(
            fn ($t) => $t->text,
            array_filter($details->translations, fn ($t) => $t->languageId === $this->ru)
        );

        $this->assertCount(2, $ruTexts);
    }

    public function test_get_entry_returns_null_for_unknown_id(): void
    {
        $this->assertNull(
            app(GetEntryUseCase::class)->handle(new GetEntryCommand('00000000-0000-0000-0000-000000000000'))
        );
    }

    public function test_languages_endpoint(): void
    {
        $response = $this->getJson('/api/catalog/languages');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_entry_endpoint_returns_meanings(): void
    {
        $response = $this->getJson('/api/catalog/entries/'.$this->entryId);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.meanings')
            ->assertJsonCount(4, 'data.translations');
    }

    public function test_clean_data_merges_typo_and_subsets(): void
    {
        $entry = Entry::query()->create(['level' => 'A1']);

        $good = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'Тест']);
        EntryTranslation::query()->create([
            'entry_id' => $entry->id, 'meaning_id' => $good->id,
            'language_id' => $this->ru, 'text' => 'Тест',
        ]);
        $goodEn = EntryTranslation::query()->create([
            'entry_id' => $entry->id, 'meaning_id' => $good->id,
            'language_id' => $this->en, 'text' => 'test',
        ]);

        $typo = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'Тестъ']);
        $typoEn = EntryTranslation::query()->create([
            'entry_id' => $entry->id, 'meaning_id' => $typo->id,
            'language_id' => $this->en, 'text' => 'test',
        ]);

        // A form anchored to the doomed translation must survive the merge.
        WordForm::query()->create([
            'entry_translation_id' => $typoEn->id, 'form' => 'tested', 'form_type' => 'past',
        ]);

        $sub = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'Проба']);
        EntryTranslation::query()->create([
            'entry_id' => $entry->id, 'meaning_id' => $sub->id,
            'language_id' => $this->ru, 'text' => 'Проба',
        ]);
        EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => 'проба; проверка']);

        // Dry run changes nothing.
        $this->artisan('catalog:clean-data')->assertSuccessful();
        $this->assertNotNull(EntryMeaning::query()->find($typo->id));

        $this->artisan('catalog:clean-data', ['--fix' => true])->assertSuccessful();

        // Typo merged into «Тест», subset merged into «проба; проверка».
        $this->assertNull(EntryMeaning::query()->find($typo->id));
        $this->assertNull(EntryMeaning::query()->where('entry_id', $entry->id)->where('note', 'Проба')->first());
        $this->assertSame(2, EntryMeaning::query()->where('entry_id', $entry->id)->count());

        // The form followed its translation to the surviving meaning.
        $this->assertSame(
            $goodEn->id,
            WordForm::query()
                ->where('form', 'tested')->value('entry_translation_id')
        );
    }
}
