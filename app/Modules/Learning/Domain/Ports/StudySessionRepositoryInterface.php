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
        ?string $categoryId = null,
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

    /**
     * Newest active session within one category context (null = no topic).
     * Each topic keeps its own resumable session.
     */
    public function findActiveSession(string $profileId, ?string $categoryId = null): ?StudySession;

    /**
     * Abandon all active sessions of the profile within one category context.
     */
    public function abandonActive(string $profileId, ?string $categoryId): int;

    public function saveSession(StudySession $session): StudySession;

    public function markItemAnswered(string $sessionId, string $learnableId, string $status, int $quality): void;

    public function nextPendingItem(string $sessionId): ?StudySessionItemRef;

    public function countByStatus(string $sessionId, string $status): int;

    /**
     * When $categoryId points to a verbs-only category, the card keeps
     * verb translations only (other parts of speech are lesson noise).
     */
    public function cardFor(
        string $profileId,
        string $learnableType,
        string $learnableId,
        ?string $categoryId = null,
    ): ?StudyCard;

    /**
     * Entries the profile has never reviewed, optionally filtered.
     * Ordered by frequency rank; $offset skips the first N (1-based position = offset + 1).
     *
     * @return list<array{learnable_type: string, learnable_id: string}>
     */
    public function findNewEntries(string $profileId, ?string $categoryId, ?string $level, int $limit, int $offset = 0): array;

    public function countNewEntries(string $profileId, ?string $categoryId, ?string $level): int;

    /**
     * Recent sessions for activity charts, newest first.
     *
     * @return list<array{started_at: ?string, finished_at: ?string, answered: int, xp_earned: int}>
     */
    public function recentSessions(string $profileId, int $days): array;

    /**
     * Wrong answer options in the requested language side for quiz modes.
     * Same source table and category first, then falls back to other tables.
     *
     * @return list<string>
     */
    public function distractors(
        string $profileId,
        string $learnableType,
        string $learnableId,
        string $side,
        ?string $categoryId,
        int $count,
    ): array;
}
