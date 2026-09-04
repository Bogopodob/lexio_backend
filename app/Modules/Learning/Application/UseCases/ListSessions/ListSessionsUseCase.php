<?php

namespace App\Modules\Learning\Application\UseCases\ListSessions;

use App\Modules\Learning\Domain\Entities\StudySession;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;
use App\Modules\Learning\Domain\Ports\StudySessionRepositoryInterface;

final readonly class ListSessionsUseCase
{
    public function __construct(
        private StudySessionRepositoryInterface $sessions,
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return list<StudySession>|null
     */
    public function handle(ListSessionsCommand $command): ?array
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return $this->sessions->listSessions($command->profileId, $command->limit);
    }
}
