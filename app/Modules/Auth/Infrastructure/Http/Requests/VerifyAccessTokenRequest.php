<?php

namespace App\Modules\Auth\Infrastructure\Http\Requests;

use App\Shared\Laravel\Infrastructure\Http\Request\AppFormRequest;

final class VerifyAccessTokenRequest extends AppFormRequest
{
    protected function headerRules(): array
    {
        return [
            'Authorization' => ['required', 'string', 'regex:/^Bearer\s+.+$/'],
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
