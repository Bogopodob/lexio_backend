<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent;

use App\Modules\User\Domain\Entities\UserAccount;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User as UserModel;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\UserProfile as UserProfileModel;
use App\Modules\Catalog\Infrastructure\Persistence\Eloquent\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class EloquentUserAccountRepository implements UserAccountRepositoryInterface
{
    public function findById(string $userId): ?UserAccount
    {
        $user = UserModel::query()->find($userId);

        return $user ? $this->toDomain($user) : null;
    }

    public function findByEmail(string $email): ?UserAccount
    {
        $user = UserModel::query()->where('email', mb_strtolower(trim($email)))->first();

        return $user ? $this->toDomain($user) : null;
    }

    public function create(string $id, string $name, string $email, string $passwordHash): UserAccount
    {
        return DB::transaction(function () use ($id, $name, $email, $passwordHash) {
            $displayName = trim($name) !== '' ? trim($name) : 'User';

            $user = UserModel::query()->create([
                'id' => $id,
                'name' => $displayName,
                'email' => mb_strtolower(trim($email)),
                'password' => $passwordHash,
            ]);

            UserProfileModel::query()->firstOrCreate(
                ['user_id' => $id],
                ['name' => $displayName],
            );

            $this->createDefaultLanguageProfile($id);

            return $this->toDomain($user);
        });
    }

    /**
     * New users get a ready-to-use English learning profile so the app
     * works (and the catalog renders) right after registration.
     */
    private function createDefaultLanguageProfile(string $userId): void
    {
        $target = Language::query()->where('code', 'en')->first();
        $native = Language::query()->where('code', 'ru')->first();

        if (! $target || ! $native) {
            return;
        }

        DB::table('user_language_profiles')->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'target_language_id' => (string) $target->id,
            'native_language_id' => (string) $native->id,
            'level' => 'A2',
            'daily_goal' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function toDomain(UserModel $user): UserAccount
    {
        return new UserAccount(
            id: (string) $user->id,
            name: (string) $user->name,
            email: (string) $user->email,
            passwordHash: (string) $user->password,
        );
    }
}
