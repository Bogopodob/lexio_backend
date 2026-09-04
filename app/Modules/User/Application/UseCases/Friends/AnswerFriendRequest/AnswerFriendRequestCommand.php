<?php

namespace App\Modules\User\Application\UseCases\Friends\AnswerFriendRequest;

final readonly class AnswerFriendRequestCommand
{
    public function __construct(
        public string $userId,
        public string $requestId,
        public bool $accept,
    ) {}
}
