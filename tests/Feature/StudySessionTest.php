<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\DemoContentSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->seed(\App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\CategorySeeder::class);
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

        // Drain the rest badly: session finishes.
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

            $this->assertLessThanOrEqual(10, $answered);
        }

        $this->assertSame($total, $answered);

        $final = $this->auth()->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions"
        )->json('data.0');

        $this->assertSame('finished', $final['status']);
        $this->assertSame($total, $final['answered']);
        $this->assertSame(1, $final['correct']);
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
        $catA = \Illuminate\Support\Facades\DB::table('categories')->where('slug', 'verbs')->value('id');
        $catB = \Illuminate\Support\Facades\DB::table('categories')->where('slug', 'nouns')->value('id');

        $this->assertNotNull($catA);
        $this->assertNotNull($catB);

        $en = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language::query()->where('code', 'en')->value('id');

        foreach ([[$catA, 'run-a'], [$catA, 'jump-a'], [$catB, 'run-b'], [$catB, 'jump-b']] as [$cat, $word]) {
            $entry = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Entry::query()->create(['level' => 'A1']);
            $meaning = \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryMeaning::query()->create([
                'entry_id' => $entry->id,
                'note' => $word,
            ]);
            \App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\EntryTranslation::query()->create([
                'entry_id' => $entry->id,
                'meaning_id' => $meaning->id,
                'language_id' => $en,
                'text' => $word,
            ]);
            \Illuminate\Support\Facades\DB::table('entry_category')->insert([
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
