<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class UpdateProfileRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'level' => ['nullable', 'string', 'size:2'],
            'daily_goal' => ['nullable', 'integer', 'min:1', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
