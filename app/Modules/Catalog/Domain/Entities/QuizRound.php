<?php

namespace App\Modules\Catalog\Domain\Entities;

final readonly class QuizRound
{
    /**
     * @param  list<string>  $options
     */
    public function __construct(
        public string $word,
        public ?string $transcription,
        public array $options,
        public int $correctIndex,
    ) {}
}
