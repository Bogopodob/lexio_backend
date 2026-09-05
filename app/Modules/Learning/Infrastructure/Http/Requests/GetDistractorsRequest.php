<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class GetDistractorsRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'learnable_type' => ['required', 'string', 'in:entry,phrase,user_entry,user_phrase'],
            'learnable_id' => ['required', 'uuid'],
            'side' => ['required', 'string', 'in:target,native'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'count' => ['nullable', 'integer', 'min:1', 'max:6'],
        ];
    }
}
