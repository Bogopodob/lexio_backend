<?php

namespace App\Modules\Library\Application\UseCases\TranscribeText;

final readonly class TranscribeTextCommand
{
    public function __construct(
        public string $text,
        public string $lang = 'en',
    ) {}
}
