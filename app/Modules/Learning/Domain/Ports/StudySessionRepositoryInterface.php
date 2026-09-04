<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\StudyCard;
use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Entities\StudySessionItemRef;

interface StudySessionRepositoryInterface
{
    public function createSession(
        string $userId,
        string $profileId,
        string $source,
        string $targetLanguageId,
        string $nativeLanguageId,
    ): StudySession;

    /**
     * @param  list<array{learnable_type: string, learnable_id: string}>  $items
     */
    public function appendItems(string $sessionId, array $items): void;

    public function findSession(string $sessionId): ?StudySession;

    /**
     * @return list<StudySession> newest first (items excluded)
     */
    public function listSessions(string $profileId, int $limit): array;

    public function findActiveSession(string $profileId): ?StudySession;

    public function saveSession(StudySession $session): StudySession;

    public function markItemAnswered(string $sessionId, string $learnableId, string $status, int $quality): void;

    public function nextPendingItem(string $sessionId): ?StudySessionItemRef;

    public function countByStatus(string $sessionId, string $status): int;

    public function cardFor(string $profileId, string $learnableType, string $learnableId): ?StudyCard;

    /**
     * Entries the profile has never reviewed, optionally filtered.
     * Ordered by frequency rank; $offset skips the first N (1-based position = offset + 1).
     *
     * @return list<array{learnable_type: string, learnable_id: string}>
     */
    public function findNewEntries(string $profileId, ?string $categoryId, ?string $level, int $limit, int $offset = 0): array;

    public function countNewEntries(string $profileId, ?string $categoryId, ?string $level): int;
}
