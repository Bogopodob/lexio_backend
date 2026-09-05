<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\CategorySeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\DemoContentSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class StudySessionTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    private string $token;

    private string $profileId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->seed(CategorySeeder::class);
        $this->seed(DemoContentSeeder::class);

        $registered = $this->postJson('/api/register', [
            'email' => 'student@example.com',
            'password' => 'secret123',
        ])->json('data');

        $this->userId = $registered['user']['id'];
        $this->token = $registered['token'];

        $en = Language::query()->where('code', 'en')->value('id');
        $ru = Language::query()->where('code', 'ru')->value('id');

        // NOTE: no withToken() here — it would poison default headers
        // for the whole test instance (including the guest test below).
        $result = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($this->userId, (string) $en, (string) $ru)
        );

        $this->profileId = $result->profile->id;
    }

    private function auth()
    {
        return $this->withToken($this->token);
    }

    private function createWord(string $en, string $ru, int $rank): void
    {
        $enId = Language::query()->where('code', 'en')->value('id');
        $ruId = Language::query()->where('code', 'ru')->value('id');

        $entry = Entry::query()->create(['level' => 'A1', 'frequency_rank' => $rank]);
        $meaning = EntryMeaning::query()->create(['entry_id' => $entry->id, 'note' => $en]);

        EntryTranslation::query()->create([
            'entry_id' => $entry->id,
            'meaning_id' => $meaning->id,
            'language_id' => $enId,
            'text' => $en,
        ]);
        EntryTranslation::query()->create([
            'entry_id' => $entry->id,
            'meaning_id' => $meaning->id,
            'language_id' => $ruId,
            'text' => $ru,
        ]);
    }

    public function test_full_lesson_flow_with_resume(): void
    {
        // Start a mixed session (demo seed provides fresh words).
        $started = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'mixed', 'limit' => 5]
        );

        $started->assertCreated();
        $total = $started->json('data.total');
        $this->assertGreaterThanOrEqual(1, $total);
        $sessionId = $started->json('data.id');

        // Session is listed as active (resume point).
        $listed = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions"
        );

        $listed->assertOk()->assertJsonPath('data.0.status', 'active');

        // First card has front/back texts.
        $next = $this->auth()->getJson("/api/learning/users/{$this->userId}/sessions/{$sessionId}/next");

        $next->assertOk()
            ->assertJsonPath('data.total', $total)
            ->assertJsonPath('data.answered', 0);
        $this->assertNotEmpty($next->json('data.card.front_text'));
        $this->assertNotEmpty($next->json('data.card.back_texts'));

        $firstId = $next->json('data.card.learnable_id');

        // Answer well: SM-2 progress created.
        $answer = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $firstId, 'quality' => 5]
        );

        $answer->assertOk()
            ->assertJsonPath('data.session.answered', 1)
            ->assertJsonPath('data.session.correct', 1);

        // Answering the same (already answered) card is rejected.
        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $firstId, 'quality' => 5]
        )->assertNotFound();

        // Drain the rest badly: each failed card is re-queued once,
        // so every remaining card is answered twice before the finish.
        $answered = 1;

        while (true) {
            $nextCard = $this->auth()
                ->getJson("/api/learning/users/{$this->userId}/sessions/{$sessionId}/next")
                ->json('data.card');

            if ($nextCard === null) {
                break;
            }

            $last = $this->auth()->postJson(
                "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
                ['learnable_id' => $nextCard['learnable_id'], 'quality' => 2]
            );

            $last->assertOk();
            $answered++;

            $this->assertLessThanOrEqual(2 * $total, $answered);
        }

        $this->assertSame(2 * $total - 1, $answered);

        $final = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions"
        )->json('data.0');

        $this->assertSame('finished', $final['status']);
        $this->assertSame(2 * $total - 1, $final['answered']);
        $this->assertSame(2 * $total - 1, $final['total']);
        $this->assertSame(1, $final['correct']);
    }

    public function test_failed_card_returns_once_at_end(): void
    {
        $this->createWord('zzkw-first', 'зз-подск-первый', 1);
        $this->createWord('zzkw-second', 'зз-подск-второй', 2);

        $started = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 2]
        );

        $started->assertJsonPath('data.total', 2);
        $sessionId = $started->json('data.id');

        $first = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        // Fail the first card: it is re-queued, deck grows by one.
        $bad = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $first['learnable_id'], 'quality' => 2]
        );

        $bad->assertOk()
            ->assertJsonPath('data.requeued', true)
            ->assertJsonPath('data.session.total', 3)
            ->assertJsonPath('data.finished', false);

        // The re-queued card waits at the end: next is the other word.
        $second = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->assertNotSame($first['learnable_id'], $second['learnable_id']);

        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $second['learnable_id'], 'quality' => 5]
        )->assertOk()->assertJsonPath('data.requeued', false);

        // The failed word is back; failing it again does NOT re-queue twice.
        $again = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->assertSame($first['learnable_id'], $again['learnable_id']);

        $last = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $again['learnable_id'], 'quality' => 2]
        );

        $last->assertOk()
            ->assertJsonPath('data.requeued', false)
            ->assertJsonPath('data.finished', true)
            ->assertJsonPath('data.session.total', 3)
            ->assertJsonPath('data.session.answered', 3);
    }

    public function test_next_card_includes_progress(): void
    {
        $entryId = DB::table('entries')->value('id');
        $this->assertNotNull($entryId);

        DB::table('user_progresses')->insert([
            'id' => (string) Uuid::uuid4(),
            'user_id' => $this->userId,
            'profile_id' => $this->profileId,
            'learnable_type' => 'entry',
            'learnable_id' => $entryId,
            'easiness_factor' => 2.1,
            'interval_days' => 6,
            'repetition' => 2,
            'quality_last' => 4,
            'next_review_at' => now()->subDay(),
            'last_reviewed_at' => now()->subDays(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sessionId = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'due', 'limit' => 5]
        )->json('data.id');

        $next = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        );

        $next->assertOk()
            ->assertJsonPath('data.card.learnable_id', (string) $entryId)
            ->assertJsonPath('data.progress.repetition', 2)
            ->assertJsonPath('data.progress.interval_days', 6)
            ->assertJsonPath('data.progress.easiness_factor', 2.1);
    }

    public function test_distractors_endpoint(): void
    {
        $this->createWord('zzkw-first', 'зз-подск-первый', 1);
        $this->createWord('zzkw-second', 'зз-подск-второй', 2);
        $this->createWord('zzkw-third', 'зз-подск-третий', 3);
        $this->createWord('zzkw-fourth', 'зз-подск-четвёртый', 4);

        $sessionId = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 1]
        )->json('data.id');

        $card = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $res = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/distractors?".http_build_query([
                'learnable_type' => $card['learnable_type'],
                'learnable_id' => $card['learnable_id'],
                'side' => 'native',
                'count' => 3,
            ])
        );

        $res->assertOk();
        $options = $res->json('data.options');

        $this->assertCount(3, $options);

        $native = array_map(fn ($t) => mb_strtolower(trim($t)), $card['native_texts']);

        foreach ($options as $opt) {
            $this->assertNotEmpty(trim($opt));
            $this->assertNotContains(mb_strtolower(trim($opt)), $native);
        }

        $this->assertCount(3, array_unique(array_map(fn ($t) => mb_strtolower(trim($t)), $options)));

        // Target side works too and validation rejects garbage.
        $target = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/distractors?".http_build_query([
                'learnable_type' => $card['learnable_type'],
                'learnable_id' => $card['learnable_id'],
                'side' => 'target',
                'count' => 2,
            ])
        );

        $target->assertOk();
        $this->assertCount(2, $target->json('data.options'));

        $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/distractors?".http_build_query([
                'learnable_type' => 'entry',
                'learnable_id' => $card['learnable_id'],
                'side' => 'weird',
            ])
        )->assertStatus(422);
    }

    public function test_word_hint_saved_and_exposed_on_card(): void
    {
        $sessionId = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 1]
        )->json('data.id');

        $card = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->assertNull($card['own_hint']);

        $saved = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/word-hint",
            [
                'learnable_type' => $card['learnable_type'],
                'learnable_id' => $card['learnable_id'],
                'own_hint' => 'моя ассоциация',
            ]
        );

        $saved->assertOk()->assertJsonPath('data.own_hint', 'моя ассоциация');

        $again = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->assertSame('моя ассоциация', $again['own_hint']);

        // Empty hint deletes it.
        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/word-hint",
            [
                'learnable_type' => $card['learnable_type'],
                'learnable_id' => $card['learnable_id'],
                'own_hint' => '',
            ]
        )->assertOk()->assertJsonPath('data.own_hint', null);
    }

    public function test_availability_counts(): void
    {
        $before = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/availability"
        );

        $before->assertOk();
        $newCount = $before->json('data.new');
        $this->assertGreaterThanOrEqual(1, $newCount);
        $this->assertSame(0, $before->json('data.due'));

        $sessionId = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 1]
        )->json('data.id');

        $card = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/next"
        )->json('data.card');

        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionId}/answer",
            ['learnable_id' => $card['learnable_id'], 'quality' => 5]
        )->assertOk();

        // reviewed word leaves the "new" pool (due comes only after its day passes)
        $after = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/availability"
        )->json('data');

        $this->assertSame($newCount - 1, $after['new']);
        $this->assertSame(0, $after['due']);
    }

    public function test_session_offset_skips_first_words(): void
    {
        $en = Language::query()->where('code', 'en')->value('id');

        foreach (['alpha', 'bravo', 'charlie'] as $i => $word) {
            $entry = Entry::query()->create([
                'level' => 'A1',
                'frequency_rank' => 100 + $i,
            ]);
            $meaning = EntryMeaning::query()->create([
                'entry_id' => $entry->id,
                'note' => $word,
            ]);
            EntryTranslation::query()->create([
                'entry_id' => $entry->id,
                'meaning_id' => $meaning->id,
                'language_id' => $en,
                'text' => $word,
            ]);
        }

        $first = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 1, 'offset' => 0]
        )->json('data');

        // Read the first card before the next session abandons this one.
        $firstCard = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$first['id']}/next"
        )->json('data.card');

        $shifted = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 1, 'offset' => 1]
        )->json('data');

        $shiftedCard = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$shifted['id']}/next"
        )->json('data.card');

        $this->assertNotSame($firstCard['learnable_id'], $shiftedCard['learnable_id']);

        // Offset beyond the pool yields nothing to learn.
        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'offset' => 100000]
        )->assertStatus(422);
    }

    public function test_each_topic_keeps_its_own_resume(): void
    {
        $catA = DB::table('categories')->where('slug', 'verbs')->value('id');
        $catB = DB::table('categories')->where('slug', 'nouns')->value('id');

        $this->assertNotNull($catA);
        $this->assertNotNull($catB);

        $en = Language::query()->where('code', 'en')->value('id');

        foreach ([[$catA, 'run-a'], [$catA, 'jump-a'], [$catB, 'run-b'], [$catB, 'jump-b']] as [$cat, $word]) {
            $entry = Entry::query()->create(['level' => 'A1']);
            $meaning = EntryMeaning::query()->create([
                'entry_id' => $entry->id,
                'note' => $word,
            ]);
            EntryTranslation::query()->create([
                'entry_id' => $entry->id,
                'meaning_id' => $meaning->id,
                'language_id' => $en,
                'text' => $word,
            ]);
            DB::table('entry_category')->insert([
                'entry_id' => $entry->id,
                'category_id' => $cat,
            ]);
        }

        // Start a lesson on topic A and answer one card.
        $sessionA = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'category_id' => $catA]
        )->json('data');

        $cardA = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionA['id']}/next"
        )->json('data.card');

        $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/sessions/{$sessionA['id']}/answer",
            ['learnable_id' => $cardA['learnable_id'], 'quality' => 4]
        )->assertOk();

        // Start a lesson on topic B: topic A's session must survive.
        $sessionB = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'category_id' => $catB]
        )->json('data');

        $this->assertSame((string) $catB, $sessionB['category_id']);

        $list = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions"
        )->json('data');

        $actives = array_values(array_filter($list, fn ($s) => $s['status'] === 'active'));
        $this->assertCount(2, $actives);

        // A fresh start inside topic A abandons only topic A's session.
        $sessionA2 = $this->auth()->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'new', 'limit' => 5, 'category_id' => $catA]
        )->json('data');

        $this->assertNotSame($sessionA['id'], $sessionA2['id']);

        $list = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions"
        )->json('data');

        $byId = [];
        foreach ($list as $s) {
            $byId[$s['id']] = $s['status'];
        }

        $this->assertSame('abandoned', $byId[$sessionA['id']]);
        $this->assertSame('active', $byId[$sessionB['id']]);
        $this->assertSame('active', $byId[$sessionA2['id']]);
    }

    public function test_guest_cannot_study(): void
    {
        $this->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'mixed']
        )->assertUnauthorized();
    }
}
