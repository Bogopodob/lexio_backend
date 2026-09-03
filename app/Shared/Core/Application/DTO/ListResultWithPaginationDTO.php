<?php

namespace App\Shared\Core\Application\DTO;

use App\Shared\Core\Application\DTO\Pagination\PaginationInfoDTO;
use Illuminate\Support\Collection;

final readonly class ListResultWithPaginationDTO implements DTOInterface
{
    public function __construct(
        public Collection $items,
        public PaginationInfoDTO $paginationInfo,
    ) {}
}
