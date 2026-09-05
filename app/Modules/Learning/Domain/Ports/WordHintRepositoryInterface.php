<?php

namespace App\Modules\Learning\Domain\Ports;

interface WordHintRepositoryInterface
{
    public function find(string $profileId, string $learnableType, string $learnableId): ?string;

    /**
     * Empty hint deletes the stored hint.
     */
    public function save(string $profileId, string $learnableType, string $learnableId, string $hint): ?string;
}
