<?php

namespace App\Modules\Auth\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class RequestEmailCodeRequest extends AppFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'locale' => ['nullable', 'string', 'max:10'],
        ];
    }
}
