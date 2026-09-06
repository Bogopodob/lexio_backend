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
        $registered = $this->postJson('/api/register', [
            'email' => 'lib@example.com',
            'password' => 'secret123',
        ])->json('data');

        $userId = $registered['user']['id'];
        $token = $registered['token'];

        $created = $this->withToken($token)->postJson("/api/library/users/{$userId}/phrases", [
            'phrase_type' => 'example',
            'translations' => [
                ['language_id' => $this->en, 'text' => 'World peace'],
                ['language_id' => $this->ru, 'text' => 'Мир во всём мире'],
            ],
        ]);

        $created->assertCreated()->assertJsonPath('success', true);

        $listed = $this->withToken($token)->getJson("/api/library/users/{$userId}/phrases?language_id={$this->ru}");

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

    private function authUser(string $email): array
    {
        $registered = $this->postJson('/api/register', [
            'email' => $email,
            'password' => 'secret123',
        ])->json('data');

        return ['id' => $registered['user']['id'], 'token' => $registered['token']];
    }

    private function uploadFile(string $content, string $name, ?string $mime = null): \Illuminate\Http\UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lib');

        if ($path === false) {
            $this->fail('No temp file');
        }

        file_put_contents($path, $content);

        return new \Illuminate\Http\UploadedFile($path, $name, $mime, null, true);
    }

    public function test_friend_learns_shared_words(): void
    {
        $anna = $this->authUser('deck-anna@example.com');
        $boris = $this->authUser('deck-boris@example.com');

        $en = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language::query()->where('code', 'en')->value('id');
        $ru = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language::query()->where('code', 'ru')->value('id');

        $catId = $this->withToken($anna['token'])->postJson('/api/catalog/categories', [
            'name' => 'Shared',
        ])->assertCreated()->json('data.id');

        $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/entries", [
            'category_id' => $catId,
            'translations' => [
                ['language_id' => $en, 'text' => 'sharedword'],
                ['language_id' => $ru, 'text' => 'общееслово'],
            ],
        ])->assertCreated();

        $profileId = app(\App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase::class)->handle(
            new \App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand(
                $boris['id'], (string) $en, (string) $ru
            )
        )->profile->id;

        // No access yet: nothing to learn.
        $this->withToken($boris['token'])->postJson(
            "/api/learning/users/{$boris['id']}/profiles/{$profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'category_id' => $catId]
        )->assertStatus(422);

        $this->befriend($anna['id'], $boris['id']);

        $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/shares", [
            'category_id' => $catId,
            'friend_user_id' => $boris['id'],
        ])->assertCreated();

        $sessionId = $this->withToken($boris['token'])->postJson(
            "/api/learning/users/{$boris['id']}/profiles/{$profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'category_id' => $catId]
        )->assertCreated()->json('data.id');

        $card = $this->withToken($boris['token'])
            ->getJson("/api/learning/users/{$boris['id']}/sessions/{$sessionId}/next")
            ->json('data.card');

        $this->assertSame('sharedword', $card['front_text']);

        $this->withToken($boris['token'])->postJson(
            "/api/learning/users/{$boris['id']}/sessions/{$sessionId}/answer",
            ['learnable_id' => $card['learnable_id'], 'quality' => 5]
        )->assertOk()->assertJsonPath('data.finished', true);
    }

    private function befriend(string $a, string $b): void
    {
        \Illuminate\Support\Facades\DB::table('friendships')->insert([
            'id' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            'requester_id' => $a,
            'addressee_id' => $b,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_shared_category_opens_words_to_friend_only(): void
    {
        $anna = $this->authUser('share-anna@example.com');
        $boris = $this->authUser('share-boris@example.com');
        $stranger = $this->authUser('share-stranger@example.com');

        $en = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language::query()->where('code', 'en')->value('id');
        $ru = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language::query()->where('code', 'ru')->value('id');

        $catId = $this->withToken($anna['token'])->postJson('/api/catalog/categories', [
            'name' => 'Наше',
        ])->assertCreated()->json('data.id');

        $entryId = $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/entries", [
            'category_id' => $catId,
            'translations' => [
                ['language_id' => $en, 'text' => 'secretword'],
                ['language_id' => $ru, 'text' => 'секретслово'],
            ],
        ])->assertCreated()->json('data.id');

        // Stranger sees nothing (cross-user URLs are blocked by ownership,
        // shared URLs answer 404).
        $this->withToken($stranger['token'])
            ->getJson("/api/library/users/{$anna['id']}/entries/{$entryId}")
            ->assertForbidden();
        $this->withToken($stranger['token'])
            ->getJson("/api/library/users/{$stranger['id']}/shared/entries?category_id={$catId}")
            ->assertNotFound();

        // Sharing with a non-friend is rejected.
        $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/shares", [
            'category_id' => $catId,
            'friend_user_id' => $boris['id'],
        ])->assertForbidden();

        $this->befriend($anna['id'], $boris['id']);

        // Sharing a system category is rejected.
        $sysCat = (string) \Ramsey\Uuid\Uuid::uuid4();
        \Illuminate\Support\Facades\DB::table('categories')->insert([
            'id' => $sysCat,
            'slug' => 'sys-cat',
            'type' => 'theme',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/shares", [
            'category_id' => $sysCat,
            'friend_user_id' => $boris['id'],
        ])->assertForbidden();

        // Grant access.
        $shareId = $this->withToken($anna['token'])->postJson("/api/library/users/{$anna['id']}/shares", [
            'category_id' => $catId,
            'friend_user_id' => $boris['id'],
        ])->assertCreated()->json('data.id');

        // Friend reads the word and the list through shared endpoints.
        $this->withToken($boris['token'])
            ->getJson("/api/library/users/{$boris['id']}/shared/entries/{$entryId}?category_id={$catId}")
            ->assertOk()->assertJsonPath('data.translations.0.text', 'secretword');
        $this->withToken($boris['token'])
            ->getJson("/api/library/users/{$boris['id']}/shared/entries?category_id={$catId}")
            ->assertOk()->assertJsonCount(1, 'data');

        // Friend cannot modify or delete.
        $this->withToken($boris['token'])->putJson(
            "/api/library/users/{$anna['id']}/entries/{$entryId}",
            ['translations' => [['language_id' => $en, 'text' => 'hacked']]]
        )->assertForbidden();
        $this->withToken($boris['token'])
            ->deleteJson("/api/library/users/{$anna['id']}/entries/{$entryId}")
            ->assertForbidden();

        // Shared-with-me listing.
        $shared = $this->withToken($boris['token'])
            ->getJson("/api/library/users/{$boris['id']}/shared-with-me");
        $shared->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $catId)
            ->assertJsonPath('data.0.words_count', 1);

        // Owner sees the grant.
        $this->withToken($anna['token'])
            ->getJson("/api/library/users/{$anna['id']}/shares?category_id={$catId}")
            ->assertOk()->assertJsonCount(1, 'data');

        // Revoke closes access again.
        $this->withToken($anna['token'])
            ->deleteJson("/api/library/users/{$anna['id']}/shares/{$shareId}")
            ->assertOk();
        $this->withToken($boris['token'])
            ->getJson("/api/library/users/{$boris['id']}/shared/entries/{$entryId}?category_id={$catId}")
            ->assertNotFound();
    }

    public function test_entry_crud_with_audio_and_category_filter(): void
    {
        $user = $this->authUser('crud@example.com');

        $catA = (string) \Ramsey\Uuid\Uuid::uuid4();
        $catB = (string) \Ramsey\Uuid\Uuid::uuid4();

        foreach ([$catA => 'mine-a', $catB => 'mine-b'] as $id => $slug) {
            \Illuminate\Support\Facades\DB::table('categories')->insert([
                'id' => $id,
                'slug' => $slug,
                'type' => 'theme',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $created = $this->withToken($user['token'])->postJson("/api/library/users/{$user['id']}/entries", [
            'category_id' => $catA,
            'translations' => [
                ['language_id' => $this->en, 'text' => 'sun', 'transcription' => 'sʌn', 'audio_path' => '/api/library/media/1'],
                ['language_id' => $this->ru, 'text' => 'солнце'],
            ],
        ]);

        $created->assertCreated();
        $entryId = $created->json('data.id');
        $this->assertSame('/api/library/media/1', $created->json('data.translations.0.audio_path'));

        // Category filter.
        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/entries?category_id={$catB}")
            ->assertOk()->assertJsonCount(0, 'data');
        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/entries?category_id={$catA}")
            ->assertOk()->assertJsonCount(1, 'data');

        // Show + update (translations replaced wholesale).
        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/entries/{$entryId}")
            ->assertOk()->assertJsonPath('data.translations.0.text', 'sun');

        $this->withToken($user['token'])->putJson("/api/library/users/{$user['id']}/entries/{$entryId}", [
            'category_id' => $catB,
            'translations' => [
                ['language_id' => $this->en, 'text' => 'sunshine'],
            ],
        ])->assertOk()->assertJsonPath('data.translations.0.text', 'sunshine');

        // Foreign user sees nothing.
        $other = $this->authUser('other@example.com');
        $this->withToken($other['token'])
            ->getJson("/api/library/users/{$user['id']}/entries/{$entryId}")
            ->assertForbidden();
        $this->withToken($other['token'])->putJson(
            "/api/library/users/{$user['id']}/entries/{$entryId}",
            ['translations' => [['language_id' => $this->en, 'text' => 'x']]]
        )->assertForbidden();
        $this->withToken($other['token'])
            ->deleteJson("/api/library/users/{$user['id']}/entries/{$entryId}")
            ->assertForbidden();

        // Delete drops the entry.
        $this->withToken($user['token'])
            ->deleteJson("/api/library/users/{$user['id']}/entries/{$entryId}")
            ->assertOk();
        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/entries/{$entryId}")
            ->assertNotFound();
    }

    public function test_phrase_image_round_trip(): void
    {
        $user = $this->authUser('img@example.com');

        $created = $this->withToken($user['token'])->postJson("/api/library/users/{$user['id']}/phrases", [
            'image_path' => '/api/library/media/9',
            'translations' => [
                ['language_id' => $this->en, 'text' => 'Good morning'],
            ],
        ]);

        $created->assertCreated()->assertJsonPath('data.image_path', '/api/library/media/9');
    }

    public function test_media_upload_and_stream(): void
    {
        $user = $this->authUser('media@example.com');

        $png = (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        $ok = $this->withToken($user['token'])->post(
            "/api/library/users/{$user['id']}/media",
            ['kind' => 'image', 'file' => $this->uploadFile($png, 'pic.png', 'image/png')],
        );

        $ok->assertCreated();
        $mediaId = $ok->json('data.id');
        $this->assertSame('image/png', $ok->json('data.mime'));

        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/media/{$mediaId}")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        // Script disguised as audio is rejected, nothing stored.
        $bad = $this->withToken($user['token'])->post(
            "/api/library/users/{$user['id']}/media",
            ['kind' => 'audio', 'file' => $this->uploadFile('<?php echo 1;', 'evil.mp3', 'audio/mpeg')],
        );

        $bad->assertStatus(422);

        // Foreign user cannot stream.
        $other = $this->authUser('media-other@example.com');
        $this->withToken($other['token'])
            ->getJson("/api/library/users/{$user['id']}/media/{$mediaId}")
            ->assertForbidden();

        // Missing media is 404.
        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/media/00000000-0000-0000-0000-000000000000")
            ->assertNotFound();
    }

    public function test_speak_and_transcribe(): void
    {
        $user = $this->authUser('tts@example.com');

        $spoken = $this->withToken($user['token'])->postJson(
            "/api/library/users/{$user['id']}/speak",
            ['text' => 'hello', 'lang' => 'en'],
        );

        $spoken->assertCreated();
        $this->assertNotEmpty($spoken->json('data.media_id'));
        $this->assertNotEmpty($spoken->json('data.transcription'));

        $mediaId = $spoken->json('data.media_id');

        $this->withToken($user['token'])
            ->getJson("/api/library/users/{$user['id']}/media/{$mediaId}")
            ->assertOk()
            ->assertHeader('Content-Type', 'audio/wav');

        // Same text reuses the cached file.
        $again = $this->withToken($user['token'])->postJson(
            "/api/library/users/{$user['id']}/speak",
            ['text' => 'hello', 'lang' => 'en'],
        );

        $again->assertCreated()->assertJsonPath('data.media_id', $mediaId);

        $tr = $this->withToken($user['token'])->postJson('/api/library/transcribe', [
            'text' => 'world', 'lang' => 'en',
        ]);

        $tr->assertOk();
        $this->assertNotEmpty($tr->json('data.transcription'));
    }
}
