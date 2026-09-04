<?php

namespace App\Modules\Learning\Domain\Entities;

final readonly class Achievement
{
    /**
     * @param  array<string, mixed>|null  $ruleExtra
     */
    public function __construct(
        public string $id,
        public string $code,
        public string $title,
        public ?string $desc,
        public string $condition,
        public string $rarity,
        public ?string $color,
        public int $rewardXp,
        public string $ruleType,
        public int $ruleTarget,
        public ?array $ruleExtra,
    ) {}
}
