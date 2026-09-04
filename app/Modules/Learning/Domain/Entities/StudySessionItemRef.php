<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class StudySessionItemRef
{
    public function __construct(
        public string $id,
        public string $learnableType,
        public string $learnableId,
        public int $position,
    ) {}
}
