<?php

namespace App\Modules\User\Domain\Entities;

final readonly class UserProfile
{
    /**
     * @param  list<string>|null  $tags
     */
    public function __construct(
        public string $userId,
        public ?string $name,
        public ?string $lastname,
        public ?string $surname,
        public ?string $avatar,
        public ?string $city = null,
        public ?string $birthDate = null,
        public ?array $tags = null,
    ) {}
}
