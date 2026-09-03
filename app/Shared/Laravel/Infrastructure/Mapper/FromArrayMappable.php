<?php

namespace App\Shared\Laravel\Infrastructure\Mapper;

interface FromArrayMappable
{
    public static function fromArray(array $data);
}
