<?php

namespace App\Shared\Laravel\Infrastructure\Persistence\Eloquent\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasTranslatedName
{
    protected function name(): Attribute
    {
        return Attribute::get(
            fn () => $this->translation?->value
        );
    }
}
