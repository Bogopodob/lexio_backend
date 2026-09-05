<?php

namespace Tests\Feature;

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
}
