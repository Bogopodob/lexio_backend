<?php

namespace App\Shared\Laravel\Infrastructure\Mapper;

use App\Shared\Core\Application\DTO\DTOInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @template T of Model
 */
interface FromModelMappable
{
    public static function fromModel(Model $model): DTOInterface;
}
