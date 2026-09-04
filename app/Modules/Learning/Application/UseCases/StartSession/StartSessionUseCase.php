<?php

namespace App\Modules\Learning\Application\UseCases\StartSession;

use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\ProgressRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;

final readonly class StartSessionUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
        private ProgressRepositoryInterface $progress,
        private StudySessionRepositoryInterface $sessions,
    ) {}

    public function handle(StartSessionCommand $command): ?StudySession
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        $limit = max(1, min(50, $command->limit));
        $source = in_array($command->source, ['due', 'new', 'mixed'], true) ? $command->source : 'mixed';
        $offset = max(0, $command->offset);

        $deck = [];

        if ($source !== 'new') {
            foreach ($this->progress->dueReviews($command->profileId, $limit) as $due) {
                $deck[] = ['learnable_type' => $due->learnableType, 'learnable_id' => $due->learnableId];
            }
        }

        if ($source !== 'due' && count($deck) < $limit) {
            foreach ($this->sessions->findNewEntries($command->profileId, $command->categoryId, $command->level, $limit - count($deck), $offset) as $fresh) {
                $deck[] = $fresh;
            }
        }

        if ($deck === []) {
            return null;
        }

        $active = $this->sessions->findActiveSession($command->profileId);

        if ($active) {
            $this->sessions->saveSession(new StudySession(
                id: $active->id,
                userId: $active->userId,
                profileId: $active->profileId,
                source: $active->source,
                status: 'abandoned',
                total: $active->total,
                answered: $active->answered,
                correct: $active->correct,
                xpEarned: $active->xpEarned,
                startedAt: $active->startedAt,
                finishedAt: $active->finishedAt,
                categoryId: $active->categoryId,
            ));
        }

        $session = $this->sessions->createSession(
            $command->userId,
            $command->profileId,
            $source,
            $profile->targetLanguageId,
            $profile->nativeLanguageId,
            $command->categoryId,
        );

        $this->sessions->appendItems($session->id, $deck);

        return $this->sessions->findSession($session->id);
    }
}
