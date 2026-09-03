<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\LanguageProfile;
use App\Modules\Learning\Domain\Entities\LanguageStat;

interface LanguageProfileRepositoryInterface
{
    /**
     * @return list<LanguageProfile>
     */
    public function listByUser(string $userId): array;

    public function find(string $profileId): ?LanguageProfile;

    public function findByUserAndTarget(string $userId, string $targetLanguageId): ?LanguageProfile;

    public function save(LanguageProfile $profile): LanguageProfile;

    public function getStat(string $profileId): ?LanguageStat;

    public function saveStat(LanguageStat $stat): LanguageStat;
}
