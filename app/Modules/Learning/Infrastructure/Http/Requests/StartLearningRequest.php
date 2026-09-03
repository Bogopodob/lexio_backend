<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class StartLearningRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'target_language_id' => ['required', 'uuid', 'exists:languages,id'],
            'native_language_id' => ['required', 'uuid', 'exists:languages,id'],
            'level' => ['nullable', 'string', 'size:2'],
            'daily_goal' => ['nullable', 'integer', 'min:1', 'max:500'],
        ];
    }
}
