<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class AnswerCardRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'learnable_id' => ['required', 'uuid'],
            'quality' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }
}
