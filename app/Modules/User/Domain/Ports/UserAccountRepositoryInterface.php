<?php

namespace App\Modules\User\Domain\Ports;

use App\Modules\User\Domain\Entities\UserAccount;

interface UserAccountRepositoryInterface
{
    public function findById(string $userId): ?UserAccount;

    public function findByEmail(string $email): ?UserAccount;

    public function create(string $id, string $name, string $email, string $passwordHash): UserAccount;
}
