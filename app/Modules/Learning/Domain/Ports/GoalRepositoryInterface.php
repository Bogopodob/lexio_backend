<?php

namespace App\Modules\Learning\Domain\Ports;

use App\Modules\Learning\Domain\Entities\UserGoal;

interface GoalRepositoryInterface
{
    /**
     * @return list<UserGoal>
     */
    public function listByProfile(string $profileId): array;

    public function countByProfile(string $profileId): int;

    public function countCompleted(string $profileId): int;

    public function find(string $goalId): ?UserGoal;

    public function save(UserGoal $goal): UserGoal;

    public function delete(string $goalId): void;
}
