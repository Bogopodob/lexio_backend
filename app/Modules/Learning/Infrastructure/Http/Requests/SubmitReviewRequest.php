<?php

namespace App\Modules\Learning\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class SubmitReviewRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'learnable_type' => ['required', 'string', 'in:entry,phrase,user_entry,user_phrase'],
            'learnable_id' => ['required', 'uuid'],
            'quality' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }
}
