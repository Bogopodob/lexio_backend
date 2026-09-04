<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class StudyCard
{
    /**
     * @param  list<string>  $backTexts
     */
    public function __construct(
        public string $learnableType,
        public string $learnableId,
        public string $frontText,
        public ?string $frontTranscription,
        public array $backTexts,
        public ?string $hint,
    ) {}
}
