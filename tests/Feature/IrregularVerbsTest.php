<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IrregularVerbsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_import_creates_verbs_forms_and_categories(): void
    {
        $this->artisan('catalog:import-irregular-verbs', [
            '--limit' => 5,
            '--no-transcribe' => true,
        ])->assertSuccessful();

        // Parent + 7 bands touched by the first verbs + rhyme groups.
        $this->assertNotNull(DB::table('categories')->where('slug', 'irregular-verbs')->value('id'));
        $this->assertNotNull(DB::table('categories')->where('slug', 'irr-50')->value('id'));
        $this->assertNotNull(DB::table('categories')->where('slug', 'irr-100')->value('id'));

        $entryId = DB::table('entry_translations')
            ->join('languages', 'languages.id', '=', 'entry_translations.language_id')
            ->where('languages.code', 'en')
            ->where('entry_translations.text', 'begin')
            ->value('entry_translations.entry_id');

        $this->assertNotNull($entryId);

        $forms = DB::table('word_forms')
            ->join(
                'entry_translations',
                'entry_translations.id',
                '=',
                'word_forms.entry_translation_id'
            )
            ->where('entry_translations.entry_id', $entryId)
            ->orderBy('word_forms.form_type')
            ->get(['word_forms.form', 'word_forms.form_type', 'word_forms.transcription']);

        // began (past) + begun (participle) with file transcriptions.
        $this->assertCount(2, $forms);
        $this->assertSame('began', $forms[0]->form);
        $this->assertSame('past', $forms[0]->form_type);
        $this->assertNotEmpty($forms[0]->transcription);
        $this->assertSame('begun', $forms[1]->form);

        // Entry keeps the conjugation formula and a frequency rank.
        $entry = DB::table('entries')->where('id', $entryId)->first();
        $this->assertNotEmpty($entry->forms_pattern);
        $this->assertNotNull($entry->frequency_rank);

        // Rerun changes nothing.
        $this->artisan('catalog:import-irregular-verbs', [
            '--limit' => 5,
            '--no-transcribe' => true,
        ])->assertSuccessful();

        $this->assertSame(
            2,
            DB::table('word_forms')
                ->join(
                    'entry_translations',
                    'entry_translations.id',
                    '=',
                    'word_forms.entry_translation_id'
                )
                ->where('entry_translations.entry_id', $entryId)
                ->count()
        );
    }

    public function test_verb_card_exposes_all_three_forms(): void
    {
        $this->artisan('catalog:import-irregular-verbs', [
            '--limit' => 5,
            '--no-transcribe' => true,
        ])->assertSuccessful();

        $registered = $this->postJson('/api/register', [
            'email' => 'verbs@example.com',
            'password' => 'secret123',
        ])->json('data');

        $userId = $registered['user']['id'];
        $token = $registered['token'];

        $en = Language::query()->where('code', 'en')->value('id');
        $ru = Language::query()->where('code', 'ru')->value('id');

        $profileId = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($userId, (string) $en, (string) $ru)
        )->profile->id;

        $categoryId = DB::table('categories')->where('slug', 'irr-50')->value('id');
        $this->assertNotNull($categoryId);

        $sessionId = $this->withToken($token)->postJson(
            "/api/learning/users/{$userId}/profiles/{$profileId}/sessions",
            ['source' => 'new', 'limit' => 1, 'category_id' => $categoryId]
        )->json('data.id');

        // Lowest frequency rank in irr-50 comes first: put/put/put.
        $card = $this->withToken($token)->getJson(
            "/api/learning/users/{$userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->assertSame('put', $card['front_text']);
        $this->assertNotEmpty($card['forms_pattern']);
        $this->assertCount(2, $card['forms']);
        $this->assertSame('put', $card['forms'][0]['form']);
        $this->assertSame('past', $card['forms'][0]['form_type']);
        $this->assertSame('put', $card['forms'][1]['form']);
        $this->assertSame('past_participle', $card['forms'][1]['form_type']);
    }
}
