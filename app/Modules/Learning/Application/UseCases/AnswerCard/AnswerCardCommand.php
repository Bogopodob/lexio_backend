<?php

namespace App\Modules\Learning\Application\UseCases\AnswerCard;

final readonly class AnswerCardCommand
{
    public function __construct(
        public string $sessionId,
        public string $userId,
        public string $learnableId,
        public int $quality,
    ) {}
}
