<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Infrastructure\Persistence\Database\Seeders\AchievementSeeder;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class GoalsAchievementsTest extends TestCase
{
    use RefreshDatabase;

    private string $userId;

    private string $token;

    private string $en;

    private string $ru;

    private string $profileId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AchievementSeeder::class);

        $this->userId = User::query()->create([
            'name' => 'Achiever',
            'email' => 'achiever@example.com',
            'password' => 'secret',
        ])->id;

        $this->en = Language::query()->create([
            'code' => 'en', 'name' => 'English', 'native_name' => 'English',
        ])->id;
        $this->ru = Language::query()->create([
            'code' => 'ru', 'name' => 'Russian', 'native_name' => 'Русский',
        ])->id;

        $registered = $this->postJson('/api/register', [
            'email' => 'goals@example.com',
            'password' => 'secret123',
        ])->json('data');

        $this->userId = $registered['user']['id'];
        $this->token = $registered['token'];

        $profile = $this->withToken($this->token)->postJson(
            "/api/learning/users/{$this->userId}/profiles",
            ['target_language_id' => $this->en, 'native_language_id' => $this->ru]
        )->json('data');

        $this->profileId = $profile['id'];
    }

    private function goalsUrl(): string
    {
        return "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/goals";
    }

    public function test_goals_crud_with_limit_of_three(): void
    {
        $ids = [];

        foreach ([['A', '#5AD4B5'], ['B', '#5B74FF'], ['C', '#F5C16A']] as [$title, $color]) {
            $res = $this->withToken($this->token)->postJson($this->goalsUrl(), [
                'title' => $title,
                'color' => $color,
            ]);

            $res->assertCreated();
            $ids[] = $res->json('data.id');
        }

        $this->withToken($this->token)->postJson($this->goalsUrl(), ['title' => 'D'])
            ->assertStatus(422);

        $list = $this->withToken($this->token)->getJson($this->goalsUrl());
        $list->assertOk()->assertJsonCount(3, 'data');

        $this->withToken($this->token)->patchJson($this->goalsUrl().'/'.$ids[0], ['progress' => 100])
            ->assertOk()
            ->assertJsonPath('data.progress', 100);

        $this->withToken($this->token)->deleteJson($this->goalsUrl().'/'.$ids[2])
            ->assertOk();

        $this->withToken($this->token)->getJson($this->goalsUrl())
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_switching_active_profile(): void
    {
        $second = $this->withToken($this->token)->postJson(
            "/api/learning/users/{$this->userId}/profiles",
            ['target_language_id' => $this->ru, 'native_language_id' => $this->en]
        )->json('data');

        $list = $this->withToken($this->token)
            ->getJson("/api/learning/users/{$this->userId}/profiles")
            ->json('data');

        $active = array_values(array_filter($list, fn ($p) => $p['is_active']));
        $this->assertCount(1, $active);
        $this->assertSame($second['id'], $active[0]['id']);

        $this->withToken($this->token)->patchJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}",
            ['is_active' => true]
        )->assertOk();

        $list = $this->withToken($this->token)
            ->getJson("/api/learning/users/{$this->userId}/profiles")
            ->json('data');

        $active = array_values(array_filter($list, fn ($p) => $p['is_active']));
        $this->assertCount(1, $active);
        $this->assertSame($this->profileId, $active[0]['id']);
    }

    public function test_achievements_list_and_first_lesson_unlock(): void
    {
        $list = $this->withToken($this->token)->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/achievements"
        );

        $list->assertOk()->assertJsonCount(9, 'data');

        $review = $this->withToken($this->token)->postJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/reviews",
            [
                'learnable_type' => 'entry',
                'learnable_id' => Uuid::uuid4()->toString(),
                'quality' => 5,
            ]
        );

        $review->assertOk()->assertJsonPath('data.newly_unlocked', ['first_lesson']);

        $after = $this->withToken($this->token)->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/achievements"
        )->json('data');

        $first = array_values(array_filter($after, fn ($a) => $a['code'] === 'first_lesson'))[0];
        $this->assertTrue($first['unlocked']);
        $this->assertSame(1, $first['progress']);

        $stats = $this->withToken($this->token)->getJson(
            "/api/learning/users/{$this->userId}/profiles/{$this->profileId}/stats"
        );

        $stats->assertOk()
            ->assertJsonPath('data.words_learned', 1)
            ->assertJsonPath('data.streak_days', 1)
            ->assertJsonPath('data.xp', 50);
    }
}
