<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhraseBlocksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_import_creates_blocks_with_transcriptions(): void
    {
        $this->artisan('catalog:import-phrases', ['--limit' => 5])->assertSuccessful();

        $this->assertNotNull(DB::table('categories')->where('slug', 'phrases')->value('id'));
        $this->assertNotNull(DB::table('categories')->where('slug', 'phr-greetings')->value('id'));

        $this->assertSame(5, DB::table('phrases')->count());

        // File transcription kept as written (multi-variant stays bracketed).
        $hello = DB::table('phrase_translations')
            ->join('languages', 'languages.id', '=', 'phrase_translations.language_id')
            ->where('languages.code', 'en')
            ->where('phrase_translations.text', 'Hello! / Hi!')
            ->value('transcription');

        $this->assertSame('[həˈləʊ] / [haɪ]', $hello);

        // Idempotent rerun.
        $this->artisan('catalog:import-phrases', ['--limit' => 5])->assertSuccessful();
        $this->assertSame(5, DB::table('phrases')->count());
    }

    public function test_phrase_block_lesson_flow(): void
    {
        $this->artisan('catalog:import-phrases')->assertSuccessful();

        // 114 parsed, 2 file-level duplicates merged into the first
        // occurrence (gaining a second block each).
        $this->assertSame(112, DB::table('phrases')->count());

        $dupId = DB::table('phrase_translations')
            ->join('languages', 'languages.id', '=', 'phrase_translations.language_id')
            ->where('languages.code', 'en')
            ->where('phrase_translations.text', 'Have a good day!')
            ->value('phrase_id');

        $this->assertSame(2, DB::table('phrases_categories')->where('phrase_id', $dupId)->count());

        $generated = DB::table('phrase_translations')
            ->join('languages', 'languages.id', '=', 'phrase_translations.language_id')
            ->where('languages.code', 'en')
            ->where('phrase_translations.text', 'Can I help you?')
            ->value('transcription');

        $this->assertNotEmpty($generated);

        $registered = $this->postJson('/api/register', [
            'email' => 'phrases@example.com',
            'password' => 'secret123',
        ])->json('data');

        $userId = $registered['user']['id'];
        $token = $registered['token'];
        $en = Language::query()->where('code', 'en')->value('id');
        $ru = Language::query()->where('code', 'ru')->value('id');

        $profileId = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($userId, (string) $en, (string) $ru)
        )->profile->id;

        $catId = DB::table('categories')->where('slug', 'phr-shopping')->value('id');
        $this->assertNotNull($catId);

        // Block exposes its phrase count.
        $cats = $this->withToken($token)->getJson(
            "/api/learning/users/{$userId}/profiles/{$profileId}/categories?type=phrase"
        )->json('data');

        $shopping = array_values(array_filter($cats, fn ($c) => $c['slug'] === 'phr-shopping'))[0];
        $this->assertSame(15, $shopping['phrases_count']);

        $sessionId = $this->withToken($token)->postJson(
            "/api/learning/users/{$userId}/profiles/{$profileId}/sessions",
            ['source' => 'new', 'limit' => 3, 'category_id' => $catId]
        )->assertCreated()->json('data.id');

        $seen = 0;

        while (true) {
            $card = $this->withToken($token)
                ->getJson("/api/learning/users/{$userId}/sessions/{$sessionId}/next")
                ->json('data.card');

            if ($card === null) {
                break;
            }

            $this->assertSame('phrase', $card['learnable_type']);
            $this->assertNotEmpty($card['front_text']);
            $this->assertNotEmpty($card['back_texts']);

            $this->withToken($token)->postJson(
                "/api/learning/users/{$userId}/sessions/{$sessionId}/answer",
                ['learnable_id' => $card['learnable_id'], 'quality' => 5]
            )->assertOk();

            $seen++;

            $this->assertLessThan(10, $seen);
        }

        $this->assertSame(3, $seen);
    }
}
