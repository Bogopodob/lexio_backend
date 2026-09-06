<?php

namespace App\Modules\Library\Application\UseCases\SpeakText;

final readonly class SpeakTextCommand
{
    public function __construct(
        public string $userId,
        public string $text,
        public string $lang = 'en',
    ) {}
}
