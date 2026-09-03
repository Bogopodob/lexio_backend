<?php

namespace App\Shared\Laravel\Infrastructure\Mapper;

use App\Shared\Core\Application\DTO\DTOInterface;
use Illuminate\Support\Collection;

interface FromArrayItemsMappable
{
    /**
     * @param  array  $data
     * @return Collection<int, DTOInterface>
     */
    public static function fromArrayItems(array $items): Collection;
}
