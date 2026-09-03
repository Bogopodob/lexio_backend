<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\DueReviews\DueReviewsCommand;
use App\Modules\Learning\Application\UseCases\DueReviews\DueReviewsUseCase;
use App\Modules\Learning\Application\UseCases\GetStats\GetStatsCommand;
use App\Modules\Learning\Application\UseCases\GetStats\GetStatsUseCase;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewCommand;
use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewUseCase;
use App\Modules\Learning\Infrastructure\Persistence\Database\Eloquent\Models\UserProgress;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class LearningTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    private string $en;

    private string $ru;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = User::query()->create([
            'name' => 'Learner',
            'email' => 'learner@example.com',
            'password' => 'secret',
        ])->id;

        $this->en = Language::query()->create([
            'code' => 'en', 'name' => 'English', 'native_name' => 'English',
        ])->id;
        $this->ru = Language::query()->create([
            'code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский',
        ])->id;
    }

    public function test_start_learning_creates_profile_with_stats(): void
    {
        $result = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($this->userId, $this->en, $this->ru, 'A2', 10)
        );

        $this->assertSame($this->userId, $result->profile->userId);
        $this->assertSame('A2', $result->profile->level);
        $this->assertNotNull($result->stat);
        $this->assertSame(0, $result->stat->xp);
    }

    public function test_start_learning_is_idempotent(): void
    {
        $useCase = app(StartLearningUseCase::class);
        $command = new StartLearningCommand($this->userId, $this->en, $this->ru);

        $first = $useCase->handle($command);
        $second = $useCase->handle($command);

        $this->assertSame($first->profile->id, $second->profile->id);
    }

    public function test_review_applies_sm2_schedule(): void
    {
        $profile = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($this->userId, $this->en, $this->ru)
        )->profile;

        $learnableId = Uuid::uuid4()->toString();

        $first = app(SubmitReviewUseCase::class)->handle(
            new SubmitReviewCommand($profile->id, $this->userId, 'entry', $learnableId, 5)
        );

        $this->assertNotNull($first);
        $this->assertTrue($first->isNewWord);
        $this->assertSame(1, $first->progress->repetition);
        $this->assertSame(1, $first->progress->intervalDays);
        $this->assertSame(50, $first->xpGained);

        $stats = app(GetStatsUseCase::class)->handle(new GetStatsCommand($profile->id, $this->userId));

        $this->assertNotNull($stats);
        $this->assertSame(1, $stats->wordsLearned);
        $this->assertSame(50, $stats->xp);
        $this->assertNotNull($stats->lastActivityAt);
    }

    public function test_failed_review_resets_repetition(): void
    {
        $profile = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($this->userId, $this->en, $this->ru)
        )->profile;

        $learnableId = Uuid::uuid4()->toString();
        $reviews = app(SubmitReviewUseCase::class);

        $reviews->handle(new SubmitReviewCommand($profile->id, $this->userId, 'entry', $learnableId, 5));
        $failed = $reviews->handle(new SubmitReviewCommand($profile->id, $this->userId, 'entry', $learnableId, 2));

        $this->assertNotNull($failed);
        $this->assertSame(0, $failed->progress->repetition);
        $this->assertSame(1, $failed->progress->intervalDays);
    }

    public function test_due_reviews_and_ownership(): void
    {
        $profile = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($this->userId, $this->en, $this->ru)
        )->profile;

        $learnableId = Uuid::uuid4()->toString();

        app(SubmitReviewUseCase::class)->handle(
            new SubmitReviewCommand($profile->id, $this->userId, 'entry', $learnableId, 2)
        );

        UserProgress::query()
            ->where('profile_id', $profile->id)
            ->update(['next_review_at' => Carbon::yesterday()->toDateTimeString()]);

        $due = app(DueReviewsUseCase::class)->handle(new DueReviewsCommand($profile->id, $this->userId, 20));

        $this->assertNotNull($due);
        $this->assertCount(1, $due);

        $foreign = app(SubmitReviewUseCase::class)->handle(
            new SubmitReviewCommand($profile->id, 'other-user', 'entry', $learnableId, 5)
        );

        $this->assertNull($foreign);
    }

    public function test_start_learning_endpoint(): void
    {
        $response = $this->postJson("/api/learning/users/{$this->userId}/profiles", [
            'target_language_id' => $this->en,
            'native_language_id' => $this->ru,
            'level' => 'B1',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.level', 'B1')
            ->assertJsonPath('data.stat.xp', 0);
    }
}
