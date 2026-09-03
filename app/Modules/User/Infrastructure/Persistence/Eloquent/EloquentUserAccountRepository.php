<?php

namespace App\Modules\User\Infrastructure\Persistence\Eloquent;

use App\Modules\User\Domain\Entities\UserAccount;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;
use App\Modules\User\Infrastructure\Persistence\Eloquent\Models\User as UserModel;

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
        $user = UserModel::query()->create([
            'id' => $id,
            'name' => trim($name) !== '' ? trim($name) : 'User',
            'email' => mb_strtolower(trim($email)),
            'password' => $passwordHash,
        ]);

        return $this->toDomain($user);
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
