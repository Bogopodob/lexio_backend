<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class StudySessionItem
{
    public function __construct(
        public string $id,
        public string $learnableType,
        public string $learnableId,
        public int $position,
        public string $status,
        public ?int $quality,
    ) {}
}
