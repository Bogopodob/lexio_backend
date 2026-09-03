<?php

namespace App\Shared\Laravel\Infrastructure\Mapper;

use App\Shared\Core\Application\DTO\Pagination\PaginationFilterDTO;

interface ToQueryParams
{
    public static function toDatabaseParams(PaginationFilterDTO $dto): array;
}
