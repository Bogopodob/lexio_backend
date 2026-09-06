<?php

namespace App\Modules\Library\Application\UseCases\ListShares;

final readonly class ListSharesCommand
{
    public function __construct(
        public string $userId,
        public string $categoryId,
    ) {}
}
