<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class StudySession
{
    /**
     * @param  list<StudySessionItem>  $items
     */
    public function __construct(
        public string $id,
        public string $userId,
        public string $profileId,
        public string $source,
        public string $status,
        public int $total,
        public int $answered,
        public int $correct,
        public int $xpEarned,
        public ?string $startedAt,
        public ?string $finishedAt,
        public array $items = [],
    ) {}
}
