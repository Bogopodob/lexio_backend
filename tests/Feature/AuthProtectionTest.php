<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email): array
    {
        $response = $this->postJson('/api/register', [
            'email' => $email,
            'password' => 'secret123',
            'name' => 'User',
        ]);

        $response->assertCreated();

        return [
            'id' => $response->json('data.user.id'),
            'token' => $response->json('data.token'),
        ];
    }

    public function test_guest_cannot_read_profile(): void
    {
        $user = $this->register('a@example.com');

        $this->getJson("/api/users/{$user['id']}/profile")->assertUnauthorized();
    }

    public function test_guest_cannot_update_profile(): void
    {
        $user = $this->register('a@example.com');

        $this->putJson("/api/users/{$user['id']}/profile", ['city' => 'X'])
            ->assertUnauthorized();
    }

    public function test_user_cannot_access_foreign_profile(): void
    {
        $a = $this->register('a@example.com');
        $b = $this->register('b@example.com');

        $this->withToken($b['token'])
            ->getJson("/api/users/{$a['id']}/profile")
            ->assertForbidden();

        $this->withToken($b['token'])
            ->putJson("/api/users/{$a['id']}/profile", ['city' => 'X'])
            ->assertForbidden();
    }

    public function test_user_can_access_own_profile(): void
    {
        $user = $this->register('a@example.com');

        $this->withToken($user['token'])
            ->putJson("/api/users/{$user['id']}/profile", ['city' => 'Казань'])
            ->assertOk()
            ->assertJsonPath('data.city', 'Казань');
    }

    public function test_guest_cannot_use_learning_and_library(): void
    {
        $user = $this->register('a@example.com');

        $this->getJson("/api/learning/users/{$user['id']}/profiles")->assertUnauthorized();
        $this->getJson("/api/library/users/{$user['id']}/entries")->assertUnauthorized();
    }

    public function test_user_cannot_use_foreign_learning_profile(): void
    {
        $a = $this->register('a@example.com');
        $b = $this->register('b@example.com');

        $this->withToken($b['token'])
            ->getJson("/api/learning/users/{$a['id']}/profiles")
            ->assertForbidden();
    }

    public function test_invalid_token_is_rejected(): void
    {
        $user = $this->register('a@example.com');

        $this->withToken('broken.token.value')
            ->getJson("/api/users/{$user['id']}/profile")
            ->assertUnauthorized();
    }
}
