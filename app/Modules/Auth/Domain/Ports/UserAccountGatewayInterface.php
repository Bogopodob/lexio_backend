<?php

namespace App\Modules\Auth\Domain\Ports;

interface UserAccountGatewayInterface
{
    /**
     * @return array{id: string, name: string, email: string, password_hash: string}|null
     */
    public function findById(string $userId): ?array;

    /**
     * @return array{id: string, name: string, email: string, password_hash: string}|null
     */
    public function findByEmail(string $email): ?array;

    public function create(string $id, string $name, string $email, string $passwordHash): void;
}
