<?php

namespace App\Modules\Learning\Application\UseCases\GetStats;

use App\Modules\Learning\Domain\Entities\LanguageStat;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class GetStatsUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    public function handle(GetStatsCommand $command): ?LanguageStat
    {
        $profile = $this->profiles->find($command->profileId);

        if (! $profile || $profile->userId !== $command->userId) {
            return null;
        }

        return $this->profiles->getStat($command->profileId);
    }
}
