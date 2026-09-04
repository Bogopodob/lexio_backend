<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\DemoContentSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
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

    public function test_guest_cannot_study(): void
    {
        $this->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/sessions",
            ['source' => 'mixed']
        )->assertUnauthorized();
    }
}
