<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileExtendedTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(string $email): array
    {
        $response = $this->postJson('/api/register', [
            'email' => $email,
            'password' => 'secret123',
            'name' => 'Alex',
        ]);

        $response->assertCreated();

        return [
            'id' => $response->json('data.user.id'),
            'token' => $response->json('data.token'),
        ];
    }

    public function test_upsert_profile_with_city_birth_date_and_tags(): void
    {
        $user = $this->authUser('alex@example.com');
        $userId = $user['id'];

        $response = $this->withToken($user['token'])->putJson("/api/users/{$userId}/profile", [
            'name' => 'Алексей',
            'city' => 'Москва',
            'birth_date' => '2002-05-15',
            'tags' => ['Путешествия', 'Кино'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.city', 'Москва')
            ->assertJsonPath('data.birth_date', '2002-05-15')
            ->assertJsonPath('data.tags', ['Путешествия', 'Кино']);

        $shown = $this->withToken($user['token'])->getJson("/api/users/{$userId}/profile");

        $shown->assertOk()->assertJsonPath('data.city', 'Москва');
    }

    public function test_profile_rejects_future_birth_date(): void
    {
        $user = $this->authUser('future@example.com');

        $this->withToken($user['token'])
            ->putJson("/api/users/{$user['id']}/profile", ['birth_date' => '2994-05-15'])
            ->assertUnprocessable();

        $this->withToken($user['token'])
            ->putJson("/api/users/{$user['id']}/profile", ['birth_date' => '1994-05-15'])
            ->assertOk()
            ->assertJsonPath('data.birth_date', '1994-05-15');
    }

    public function test_profile_rejects_too_many_tags(): void
    {
        $user = $this->authUser('alex2@example.com');
        $userId = $user['id'];

        $response = $this->withToken($user['token'])->putJson("/api/users/{$userId}/profile", [
            'tags' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        ]);

        $response->assertUnprocessable();
    }
}
