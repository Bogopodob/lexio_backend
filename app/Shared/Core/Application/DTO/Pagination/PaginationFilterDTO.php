<?php

namespace App\Shared\Core\Application\DTO\Pagination;

use App\Shared\Core\Application\DTO\DTOInterface;
use App\Shared\Core\Domain\Constants\Pagination;

final readonly class PaginationFilterDTO implements DTOInterface
{
    public function __construct(
        public int $page = Pagination::DEFAULT_PAGE,
        public int $limit = Pagination::DEFAULT_LIMIT,
    ) {}

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
        ];
    }
}
