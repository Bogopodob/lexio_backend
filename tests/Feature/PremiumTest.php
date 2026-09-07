<?php

namespace Tests\Feature;

use App\Modules\Catalog\Infrastructure\Persistence\Database\Seeders\LanguageSeeder;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningCommand;
use App\Modules\Learning\Application\UseCases\StartLearning\StartLearningUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PremiumTest extends TestCase
{
    use RefreshDatabase;

    public function test_premium_flag_flows_through_grant_and_api(): void
    {
        $registered = $this->postJson('/api/register', [
            'email' => 'premium@example.com',
            'password' => 'secret123',
        ])->json('data');

        $userId = $registered['user']['id'];
        $token = $registered['token'];

        $this->assertFalse($registered['user']['is_premium']);

        $this->withToken($token)->getJson("/api/users/{$userId}/profile")
            ->assertOk()
            ->assertJsonPath('data.is_premium', false);

        $this->artisan('user:grant-premium', ['email' => 'premium@example.com'])
            ->assertSuccessful();

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.is_premium', true);

        $this->withToken($token)->getJson("/api/users/{$userId}/profile")
            ->assertOk()
            ->assertJsonPath('data.is_premium', true);

        $this->artisan('user:grant-premium', ['email' => 'premium@example.com', '--revoke' => true])
            ->assertSuccessful();

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.is_premium', false);

        $this->artisan('user:grant-premium', ['email' => 'ghost@example.com'])
            ->assertFailed();
    }

    public function test_stats_endpoints_require_premium(): void
    {
        $this->seed(LanguageSeeder::class);

        $registered = $this->postJson('/api/register', [
            'email' => 'stats@example.com',
            'password' => 'secret123',
        ])->json('data');

        $userId = $registered['user']['id'];
        $token = $registered['token'];

        $en = Language::query()->where('code', 'en')->value('id');
        $ru = Language::query()->where('code', 'ru')->value('id');

        $result = app(StartLearningUseCase::class)->handle(
            new StartLearningCommand($userId, (string) $en, (string) $ru)
        );

        $profileId = $result->profile->id;
        $base = "/api/learning/users/{$userId}/profiles/{$profileId}";

        // No premium — real statistics must never leak.
        $this->withToken($token)->getJson("{$base}/stats")->assertForbidden();
        $this->withToken($token)->getJson("{$base}/weekly?days=7")->assertForbidden();
        $this->withToken($token)->getJson("{$base}/due?limit=100")->assertForbidden();

        $this->artisan('user:grant-premium', ['email' => 'stats@example.com'])
            ->assertSuccessful();

        $this->withToken($token)->getJson("{$base}/stats")->assertOk();
        $this->withToken($token)->getJson("{$base}/weekly?days=7")->assertOk();
        $this->withToken($token)->getJson("{$base}/due?limit=100")->assertOk();
    }
}
