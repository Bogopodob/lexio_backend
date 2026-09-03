<?php

namespace Tests\Feature;

use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_upsert_profile_with_city_birth_date_and_tags(): void
    {
        $userId = User::query()->create([
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'secret',
        ])->id;

        $response = $this->putJson("/api/users/{$userId}/profile", [
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

        $shown = $this->getJson("/api/users/{$userId}/profile");

        $shown->assertOk()->assertJsonPath('data.city', 'Москва');
    }

    public function test_profile_rejects_too_many_tags(): void
    {
        $userId = User::query()->create([
            'name' => 'Alex',
            'email' => 'alex2@example.com',
            'password' => 'secret',
        ])->id;

        $response = $this->putJson("/api/users/{$userId}/profile", [
            'tags' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        ]);

        $response->assertUnprocessable();
    }
}
