<?php

namespace App\Shared\Core\Application\DTO\Pagination;

use App\Shared\Core\Application\DTO\DTOInterface;
use App\Shared\Core\Domain\Constants\Pagination;

final readonly class PaginationInfoDTO implements DTOInterface
{
    public function __construct(
        public int $pages = Pagination::DEFAULT_PAGES,
        public int $total = Pagination::DEFAULT_TOTAL,
        public int $page = Pagination::DEFAULT_PAGE,
        public int $limit = Pagination::DEFAULT_LIMIT,
    ) {}

    public function toArray(): array
    {
        return [
            'pages' => $this->pages,
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
        ];
    }
}
