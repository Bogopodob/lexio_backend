<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class StartSessionRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'source' => ['nullable', 'string', 'in:due,new,mixed'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'level' => ['nullable', 'string', 'size:2'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
