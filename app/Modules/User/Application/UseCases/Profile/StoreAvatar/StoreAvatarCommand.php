<?php

namespace App\Modules\User\Application\UseCases\Profile\StoreAvatar;

final readonly class StoreAvatarCommand
{
    public function __construct(
        public string $userId,
    ) {}
}
