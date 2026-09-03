<?php

namespace App\Modules\Auth\Infrastructure\Persistence\Adapters;

use App\Modules\Auth\Domain\Ports\UserAccountGatewayInterface;
use App\Modules\User\Domain\Entities\UserAccount;
use App\Modules\User\Domain\Ports\UserAccountRepositoryInterface;

final readonly class UserRepositoryUserAccountGateway implements UserAccountGatewayInterface
{
    public function __construct(private UserAccountRepositoryInterface $users) {}

    public function findById(string $userId): ?array
    {
        $user = $this->users->findById($userId);

        return $user ? $this->toArray($user) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $user = $this->users->findByEmail($email);

        return $user ? $this->toArray($user) : null;
    }

    public function create(string $id, string $name, string $email, string $passwordHash): void
    {
        $this->users->create($id, $name, $email, $passwordHash);
    }

    /**
     * @return array{id: string, name: string, email: string, password_hash: string}
     */
    private function toArray(UserAccount $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password_hash' => $user->passwordHash,
        ];
    }
}
