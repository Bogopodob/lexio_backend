<?php

namespace App\Modules\Learning\Application\UseCases\AnswerCard;

use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewCommand;
use App\Modules\Learning\Application\UseCases\SubmitReview\SubmitReviewUseCase;
use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;
use Carbon\Carbon;

final readonly class AnswerCardUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
        private SubmitReviewUseCase $submitReview,
    ) {}

    public function handle(AnswerCardCommand $command): ?AnswerCardResult
    {
        $session = $this->sessions->findSession($command->sessionId);

        if (! $session || $session->userId !== $command->userId || $session->status !== 'active') {
            return null;
        }

        $next = $this->sessions->nextPendingItem($command->sessionId);

        if (! $next || $next->learnableId !== $command->learnableId) {
            return null;
        }

        $quality = max(0, min(5, $command->quality));

        $review = $this->submitReview->handle(new SubmitReviewCommand(
            profileId: $session->profileId,
            userId: $session->userId,
            learnableType: $next->learnableType,
            learnableId: $next->learnableId,
            quality: $quality,
        ));

        if (! $review) {
            return null;
        }

        $correct = $quality >= 3;

        $this->sessions->markItemAnswered(
            $command->sessionId,
            $next->learnableId,
            $correct ? 'correct' : 'wrong',
            $quality,
        );

        $answered = $session->answered + 1;

        $updated = $this->sessions->saveSession(new StudySession(
            id: $session->id,
            userId: $session->userId,
            profileId: $session->profileId,
            source: $session->source,
            status: $session->status,
            total: $session->total,
            answered: $answered,
            correct: $session->correct + ($correct ? 1 : 0),
            xpEarned: $session->xpEarned + $review->xpGained,
            startedAt: $session->startedAt,
            finishedAt: $session->finishedAt,
            categoryId: $session->categoryId,
        ));

        $following = $this->sessions->nextPendingItem($command->sessionId);
        $finished = $following === null;

        if ($finished) {
            $updated = $this->sessions->saveSession(new StudySession(
                id: $updated->id,
                userId: $updated->userId,
                profileId: $updated->profileId,
                source: $updated->source,
                status: 'finished',
                total: $updated->total,
                answered: $updated->answered,
                correct: $updated->correct,
                xpEarned: $updated->xpEarned,
                startedAt: $updated->startedAt,
                finishedAt: Carbon::now()->toDateTimeString(),
                categoryId: $updated->categoryId,
            ));
        }

        $nextCard = $following
            ? $this->sessions->cardFor($session->profileId, $following->learnableType, $following->learnableId)
            : null;

        return new AnswerCardResult($updated, $review, $nextCard, $finished);
    }
}
