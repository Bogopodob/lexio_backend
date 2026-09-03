<?php

namespace App\Shared\Laravel\Infrastructure\Mapper\Pagination;

use App\Shared\Core\Application\DTO\Pagination\PaginationFilterDTO;
use App\Shared\Core\Domain\Constants\Pagination;
use App\Shared\Laravel\Infrastructure\Mapper\FromRequestMappable;
use App\Shared\Laravel\Infrastructure\Mapper\MapperInterface;
use App\Shared\Laravel\Infrastructure\Mapper\ToQueryParams;
use Illuminate\Http\Request;

final readonly class PaginationFilterMapper implements FromRequestMappable, MapperInterface, ToQueryParams
{
    public static function fromRequest(Request $request, ?string $section = null): PaginationFilterDTO
    {
        return new PaginationFilterDTO(
            page: $request->integer('page', Pagination::DEFAULT_PAGE),
            limit: $request->integer('limit', Pagination::DEFAULT_LIMIT),
        );
    }

    public static function toDatabaseParams(PaginationFilterDTO $dto): array
    {
        return [
            'limit' => $dto->limit,
            'offset' => ($dto->page - 1) * $dto->limit,
        ];
    }
}
