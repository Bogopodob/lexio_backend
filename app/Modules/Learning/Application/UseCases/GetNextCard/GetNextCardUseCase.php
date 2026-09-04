<?php

namespace App\Modules\Learning\Application\UseCases\GetNextCard;

use App\Modules\Learning\Domain\Entities\StudyCard;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;

final readonly class GetNextCardUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
    ) {}

    /**
     * @return array{session_id: string, position: int, total: int, answered: int, card: StudyCard}|null
     */
    public function handle(GetNextCardCommand $command): ?array
    {
        $session = $this->sessions->findSession($command->sessionId);

        if (! $session || $session->userId !== $command->userId || $session->status !== 'active') {
            return null;
        }

        $next = $this->sessions->nextPendingItem($command->sessionId);

        if (! $next) {
            return null;
        }

        $card = $this->sessions->cardFor($session->profileId, $next->learnableType, $next->learnableId);

        if (! $card) {
            return null;
        }

        return [
            'session_id' => $session->id,
            'position' => $next->position,
            'total' => $session->total,
            'answered' => $session->answered,
            'card' => $card,
        ];
    }
}
