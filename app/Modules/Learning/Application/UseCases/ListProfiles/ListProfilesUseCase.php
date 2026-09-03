<?php

namespace App\Modules\Learning\Application\UseCases\ListProfiles;

use App\Modules\Learning\Domain\Entities\ProfileWithStat;
use App\Modules\Learning\Domain\Ports\LanguageProfileRepositoryInterface;

final readonly class ListProfilesUseCase
{
    public function __construct(
        private LanguageProfileRepositoryInterface $profiles,
    ) {}

    /**
     * @return list<ProfileWithStat>
     */
    public function handle(ListProfilesCommand $command): array
    {
        $result = [];

        foreach ($this->profiles->listByUser($command->userId) as $profile) {
            $result[] = new ProfileWithStat($profile, $this->profiles->getStat($profile->id));
        }

        return $result;
    }
}
