<?php

namespace App\Modules\Library\Application\UseCases\SpeakText;

use App\Modules\Library\Domain\Entities\LibraryMedia;

final readonly class SpeakTextResult
{
    public function __construct(
        public LibraryMedia $media,
        public ?string $transcription,
    ) {}
}
