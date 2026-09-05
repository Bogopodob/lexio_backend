<?php

namespace App\Modules\Learning\Application\UseCases\SaveWordHint;

final readonly class SaveWordHintCommand
{
    public function __construct(
        public string $profileId,
        public string $userId,
        public string $learnableType,
        public string $learnableId,
        public string $hint,
    ) {}
}
