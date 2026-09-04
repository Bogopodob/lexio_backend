<?php

namespace App\Modules\Learning\Application\UseCases\FinishSession;

use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;
use Carbon\Carbon;

final readonly class FinishSessionUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
    ) {}

    public function handle(FinishSessionCommand $command): ?StudySession
    {
        $session = $this->sessions->findSession($command->sessionId);

        if (! $session || $session->userId !== $command->userId || $session->status !== 'active') {
            return null;
        }

        return $this->sessions->saveSession(new StudySession(
            id: $session->id,
            userId: $session->userId,
            profileId: $session->profileId,
            source: $session->source,
            status: 'finished',
            total: $session->total,
            answered: $session->answered,
            correct: $session->correct,
            xpEarned: $session->xpEarned,
            startedAt: $session->startedAt,
            finishedAt: Carbon::now()->toDateTimeString(),
        ));
    }
}
