<?php

namespace App\Modules\Catalog\Application\UseCases\QuizRound;

final readonly class QuizRoundCommand
{
    public function __construct(
        public string $enLanguageId,
        public string $ruLanguageId,
        public int $count = 4,
    ) {}
}
