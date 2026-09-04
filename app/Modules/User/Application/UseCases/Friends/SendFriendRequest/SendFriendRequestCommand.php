<?php

namespace App\Modules\User\Application\UseCases\Friends\SendFriendRequest;

final readonly class SendFriendRequestCommand
{
    public function __construct(
        public string $userId,
        public ?string $addresseeId = null,
        public ?string $email = null,
    ) {}
}
