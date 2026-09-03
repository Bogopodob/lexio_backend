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
}
