<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Library\Application\UseCases\ListUserEntries\ListUserEntriesCommand;
use App\Modules\Library\Application\UseCases\ListUserEntries\ListUserEntriesUseCase;
use App\Modules\Library\Application\UseCases\SaveUserEntry\SaveUserEntryCommand;
use App\Modules\Library\Application\UseCases\SaveUserEntry\SaveUserEntryUseCase;
use App\Modules\Library\Application\UseCases\SaveUserPhrase\SaveUserPhraseCommand;
use App\Modules\Library\Application\UseCases\SaveUserPhrase\SaveUserPhraseUseCase;
use App\Modules\Library\Domain\Entities\UserEntryTranslation;
use App\Modules\Library\Domain\Entities\UserPhraseTranslation;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    private string $en;

    private string $ru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = User::query()->create([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'password' => 'secret',
        ])->id;

        $this->en = Language::query()->create([
            'code' => 'en', 'name' => 'English', 'native_name' => 'English',
        ])->id;
        $this->ru = Language::query()->create([
            'code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский',
        ])->id;
    }

    public function test_save_entry_with_translations(): void
    {
        $entry = app(SaveUserEntryUseCase::class)->handle(
            new SaveUserEntryCommand(
                userId: $this->userId,
                translations: [
                    new UserEntryTranslation('', $this->en, 'peace', 'piːs', 'noun', null),
                    new UserEntryTranslation('', $this->ru, 'мир', null, 'noun', null),
                ],
            )
        );

        $this->assertCount(2, $entry->translations);
    }

    public function test_list_entries_filtered_by_language(): void
    {
        app(SaveUserEntryUseCase::class)->handle(
            new SaveUserEntryCommand(
                userId: $this->userId,
                translations: [new UserEntryTranslation('', $this->en, 'world', null, 'noun', null)],
            )
        );

        $all = app(ListUserEntriesUseCase::class)->handle(new ListUserEntriesCommand($this->userId));
        $ruOnly = app(ListUserEntriesUseCase::class)->handle(new ListUserEntriesCommand($this->userId, $this->ru));

        $this->assertCount(1, $all);
        $this->assertCount(0, $ruOnly);
    }

    public function test_save_and_list_phrases_via_endpoint(): void
    {
        $created = $this->postJson("/api/library/users/{$this->userId}/phrases", [
            'phrase_type' => 'example',
            'translations' => [
                ['language_id' => $this->en, 'text' => 'World peace'],
                ['language_id' => $this->ru, 'text' => 'Мир во всём мире'],
            ],
        ]);

        $created->assertCreated()->assertJsonPath('success', true);

        $listed = $this->getJson("/api/library/users/{$this->userId}/phrases?language_id={$this->ru}");

        $listed->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.translations.0.text', 'Мир во всём мире');
    }

    public function test_save_phrase_use_case(): void
    {
        $phrase = app(SaveUserPhraseUseCase::class)->handle(
            new SaveUserPhraseCommand(
                userId: $this->userId,
                phraseType: 'idiom',
                translations: [new UserPhraseTranslation('', $this->en, 'Break a leg', null, null)],
            )
        );

        $this->assertSame('idiom', $phrase->phraseType);
        $this->assertCount(1, $phrase->translations);
    }
}
