<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email, string $name = 'User'): array
    {
        $response = $this->postJson('/api/register', [
            'email' => $email,
            'password' => 'secret123',
            'name' => $name,
        ]);

        $response->assertCreated();

        return [
            'id' => $response->json('data.user.id'),
            'token' => $response->json('data.token'),
        ];
    }

    private function grant(string ...$emails): void
    {
        foreach ($emails as $email) {
            $this->artisan('user:grant-premium', ['email' => $email])->assertSuccessful();
        }
    }

    public function test_friend_request_flow(): void
    {
        $anna = $this->register('anna@example.com', 'Анна');
        $boris = $this->register('boris@example.com', 'Борис');
        $this->grant('anna@example.com');

        // Guest cannot list friends.
        $this->getJson("/api/users/{$anna['id']}/friends")->assertUnauthorized();

        // Anna sends request to Boris by email.
        $sent = $this->withToken($anna['token'])->postJson(
            "/api/users/{$anna['id']}/friends/requests",
            ['email' => 'boris@example.com']
        );

        $sent->assertCreated()->assertJsonPath('data.status', 'pending');
        $requestId = $sent->json('data.request_id');

        // Not friends yet.
        $this->withToken($anna['token'])
            ->getJson("/api/users/{$anna['id']}/friends")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Anna cannot accept her own outgoing request.
        $this->withToken($anna['token'])
            ->postJson("/api/users/{$anna['id']}/friends/requests/{$requestId}/accept")
            ->assertNotFound();

        // Boris sees incoming request and accepts.
        $incoming = $this->withToken($boris['token'])
            ->getJson("/api/users/{$boris['id']}/friends/requests?direction=incoming");

        $incoming->assertOk()->assertJsonCount(1, 'data');

        $this->withToken($boris['token'])
            ->postJson("/api/users/{$boris['id']}/friends/requests/{$requestId}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        // Now both see each other with profile data.
        $friends = $this->withToken($anna['token'])
            ->getJson("/api/users/{$anna['id']}/friends");

        $friends->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Борис');

        // Boris cannot touch Anna's outgoing box.
        $this->withToken($boris['token'])
            ->postJson("/api/users/{$anna['id']}/friends/requests", ['email' => 'boris@example.com'])
            ->assertForbidden();

        // Anna removes Boris.
        $friendshipId = $friends->json('data.0.friendship_id');

        $this->withToken($anna['token'])
            ->deleteJson("/api/users/{$anna['id']}/friends/{$friendshipId}")
            ->assertOk();

        $this->withToken($anna['token'])
            ->getJson("/api/users/{$anna['id']}/friends")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_cross_requests_auto_accept(): void
    {
        $anna = $this->register('anna@example.com', 'Анна');
        $boris = $this->register('boris@example.com', 'Борис');
        $this->grant('anna@example.com', 'boris@example.com');

        $this->withToken($anna['token'])->postJson(
            "/api/users/{$anna['id']}/friends/requests",
            ['user_id' => $boris['id']]
        )->assertCreated();

        // Boris requests back: friendship forms immediately.
        $this->withToken($boris['token'])->postJson(
            "/api/users/{$boris['id']}/friends/requests",
            ['user_id' => $anna['id']]
        )->assertCreated()->assertJsonPath('data.status', 'accepted');

        $this->withToken($boris['token'])
            ->getJson("/api/users/{$boris['id']}/friends")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_users(): void
    {
        $anna = $this->register('anna@example.com', 'Анна');

        $found = $this->withToken($anna['token'])->getJson(
            "/api/users/{$anna['id']}/friends/search?query=bor"
        );

        $found->assertOk()->assertJsonCount(0, 'data');

        $this->register('boris@example.com', 'Борис');

        $found = $this->withToken($anna['token'])->getJson(
            "/api/users/{$anna['id']}/friends/search?query=bor"
        );

        $found->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Борис')
            ->assertJsonPath('data.0.relation', null);

        // Too short query returns nothing.
        $this->withToken($anna['token'])->getJson(
            "/api/users/{$anna['id']}/friends/search?query=b"
        )->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_friend_requests_and_leaderboard_require_premium(): void
    {
        $anna = $this->register('anna@example.com', 'Анна');

        $this->withToken($anna['token'])->postJson(
            "/api/users/{$anna['id']}/friends/requests",
            ['email' => 'boris@example.com']
        )->assertForbidden()->assertJsonPath('message', 'Premium subscription required');

        $this->withToken($anna['token'])
            ->getJson("/api/users/{$anna['id']}/friends/leaderboard")
            ->assertForbidden();

        $this->grant('anna@example.com');

        $board = $this->withToken($anna['token'])
            ->getJson("/api/users/{$anna['id']}/friends/leaderboard");

        $board->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_self', true)
            ->assertJsonPath('data.0.user_id', $anna['id']);
    }

    public function test_decline_request(): void
    {
        $anna = $this->register('anna@example.com', 'Анна');
        $boris = $this->register('boris@example.com', 'Борис');
        $this->grant('anna@example.com');

        $requestId = $this->withToken($anna['token'])->postJson(
            "/api/users/{$anna['id']}/friends/requests",
            ['user_id' => $boris['id']]
        )->json('data.request_id');

        $this->withToken($boris['token'])
            ->postJson("/api/users/{$boris['id']}/friends/requests/{$requestId}/decline")
            ->assertOk()
            ->assertJsonPath('data.status', 'declined');

        $this->withToken($boris['token'])
            ->getJson("/api/users/{$boris['id']}/friends")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
