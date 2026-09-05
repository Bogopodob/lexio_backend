<?php

namespace App\Modules\Catalog\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SaveCategoryRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:60'],
            'locale' => ['nullable', 'string', 'in:ru,en'],
            'color' => ['nullable', 'string', 'max:15'],
            'icon' => ['nullable', 'string', 'max:16'],
            'parent_id' => ['nullable', 'uuid', 'exists:categories,id'],
        ];
    }
}
