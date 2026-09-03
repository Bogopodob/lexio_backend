<?php

namespace App\Shared\Laravel\Infrastructure\Mapper;

use Illuminate\Http\Request;

interface FromRequestMappable
{
    public static function fromRequest(Request $request);
}
