<?php

namespace App\Modules\Learning\Application\UseCases\AnswerCard;

use App\Modules\Learning\Domain\Entities\ReviewResult;
use App\Modules\Learning\Domain\Entities\StudyCard;
use App\Modules\Learning\Domain\Entities\StudySession;

final readonly class AnswerCardResult
{
    public function __construct(
        public StudySession $session,
        public ReviewResult $review,
        public ?StudyCard $nextCard,
        public bool $finished,
    ) {}
}
