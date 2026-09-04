<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class ReviewResult
{
    /**
     * @param  list<string>  $newlyUnlocked  achievement codes unlocked by this review
     */
    public function __construct(
        public ReviewProgress $progress,
        public bool $isNewWord,
        public int $xpGained,
        public array $newlyUnlocked = [],
    ) {}
}
