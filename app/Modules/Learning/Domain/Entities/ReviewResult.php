<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class ReviewResult
{
    public function __construct(
        public ReviewProgress $progress,
        public bool $isNewWord,
        public int $xpGained,
    ) {}
}
